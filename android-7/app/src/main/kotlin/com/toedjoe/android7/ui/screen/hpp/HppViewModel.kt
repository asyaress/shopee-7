package com.toedjoe.android7.ui.screen.hpp

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.toedjoe.android7.data.remote.HppPriorityData
import com.toedjoe.android7.data.remote.HppPrioritySummary
import com.toedjoe.android7.data.remote.SaveHppProductInput
import com.toedjoe.android7.data.remote.SaveHppVariantInput
import com.toedjoe.android7.data.repository.PlanningRepository
import com.toedjoe.android7.support.SessionCoordinator
import com.toedjoe.android7.support.toAppUiError
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

enum class HppQuickFilter(val label: String) {
    NeedHpp("Need HPP"),
    Priority("Priority"),
    Ready("Ready"),
    All("All"),
}

data class HppEditorVariantItem(
    val id: Int,
    val name: String,
    val sku: String = "-",
    val basePrice: Int? = null,
    val hppAmountInput: String = "",
    val packagingType: String? = null,
    val packagingValueInput: String = "",
    val originalHppAmountInput: String = "",
    val originalPackagingType: String? = null,
    val originalPackagingValueInput: String = "",
    val effectiveHppAmount: Int? = null,
    val effectivePackagingType: String = "fixed",
    val effectivePackagingValue: Int? = null,
    val inheritsProduct: Boolean = true,
    val missingHpp: Boolean = false,
    val effectiveMissingHpp: Boolean = false,
)

data class HppEditorItem(
    val id: Int,
    val name: String,
    val sku: String = "-",
    val category: String = "-",
    val basePrice: Int? = null,
    val isPriority: Boolean = false,
    val missingHpp: Boolean = false,
    val hppAmountInput: String = "",
    val packagingType: String = "fixed",
    val packagingValueInput: String = "",
    val originalHppAmountInput: String = "",
    val originalPackagingType: String = "fixed",
    val originalPackagingValueInput: String = "",
    val variants: List<HppEditorVariantItem> = emptyList(),
)

data class HppUiState(
    val isLoading: Boolean = true,
    val isSaving: Boolean = false,
    val error: String? = null,
    val successMessage: String? = null,
    val shopLabel: String = "",
    val searchQuery: String = "",
    val selectedFilter: HppQuickFilter = HppQuickFilter.NeedHpp,
    val selectedProductId: Int? = null,
    val summary: HppPrioritySummary = HppPrioritySummary(),
    val products: List<HppEditorItem> = emptyList(),
)

@HiltViewModel
class HppViewModel @Inject constructor(
    private val planningRepository: PlanningRepository,
    private val sessionCoordinator: SessionCoordinator,
) : ViewModel() {
    private val _uiState = MutableStateFlow(HppUiState())
    val uiState: StateFlow<HppUiState> = _uiState.asStateFlow()

    init {
        loadProducts()
    }

    fun updateSearchQuery(value: String) {
        _uiState.update { it.copy(searchQuery = value, error = null, successMessage = null) }
    }

    fun selectFilter(filter: HppQuickFilter) {
        _uiState.update { current ->
            current.copy(
                selectedFilter = filter,
                selectedProductId = current.selectedProductId
                    ?.takeIf { id -> current.products.filteredBy(filter).any { it.id == id } },
                error = null,
                successMessage = null,
            )
        }
    }

    fun refresh() {
        loadProducts()
    }

    fun search() {
        loadProducts()
    }

    fun openEditor(id: Int) {
        _uiState.update { it.copy(selectedProductId = id, error = null, successMessage = null) }
    }

    fun closeEditor() {
        _uiState.update { it.copy(selectedProductId = null, error = null) }
    }

    fun skipToNext() {
        _uiState.update { current ->
            current.copy(
                selectedProductId = nextProductId(current),
                successMessage = null,
                error = null,
            )
        }
    }

    fun updateHppAmount(id: Int, value: String) = updateProduct(id) {
        it.copy(hppAmountInput = value.toDigitsInput())
    }

    fun updatePackagingType(id: Int, value: String) = updateProduct(id) {
        it.copy(
            packagingType = value,
            packagingValueInput = it.packagingValueInput.toDigitsInput(),
        )
    }

    fun updatePackagingValue(id: Int, value: String) = updateProduct(id) {
        it.copy(packagingValueInput = value.toDigitsInput())
    }

    fun updateVariantHppAmount(productId: Int, variantId: Int, value: String) = updateProduct(productId) { product ->
        product.copy(
            variants = product.variants.map { variant ->
                if (variant.id == variantId) variant.copy(hppAmountInput = value.toDigitsInput()) else variant
            },
        )
    }

    fun updateVariantPackagingType(productId: Int, variantId: Int, value: String?) = updateProduct(productId) { product ->
        product.copy(
            variants = product.variants.map { variant ->
                if (variant.id != variantId) {
                    variant
                } else {
                    variant.copy(
                        packagingType = value,
                        packagingValueInput = if (value == null) "" else variant.packagingValueInput.toDigitsInput(),
                    )
                }
            },
        )
    }

    fun updateVariantPackagingValue(productId: Int, variantId: Int, value: String) = updateProduct(productId) { product ->
        product.copy(
            variants = product.variants.map { variant ->
                if (variant.id == variantId) variant.copy(packagingValueInput = value.toDigitsInput()) else variant
            },
        )
    }

    fun saveSelected() {
        persistSelected(moveNext = false)
    }

    fun saveSelectedAndNext() {
        persistSelected(moveNext = true)
    }

    private fun persistSelected(moveNext: Boolean) {
        val snapshot = _uiState.value
        val selected = snapshot.selectedProduct ?: return
        val payload = selected.toSaveInputOrNull()
        val nextSelectedId = if (moveNext) nextProductId(snapshot) else selected.id

        if (payload == null) {
            _uiState.update {
                it.copy(
                    successMessage = "Belum ada perubahan untuk disimpan.",
                    selectedProductId = if (moveNext) nextSelectedId else it.selectedProductId,
                    error = null,
                )
            }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null, successMessage = null) }

            runCatching {
                val saveResult = planningRepository.saveHpp(listOf(payload))
                val refreshed = planningRepository.hppPriority(
                    search = _uiState.value.searchQuery.trim(),
                    month = null,
                    limit = null,
                )
                saveResult.message to refreshed
            }.onSuccess { (message, refreshed) ->
                applyHppData(
                    data = refreshed,
                    successMessage = message,
                    selectedProductId = nextSelectedId,
                )
            }.onFailure { throwable ->
                val error = throwable.toAppUiError("Terjadi kesalahan saat menyimpan Quick HPP.")
                viewModelScope.launch { sessionCoordinator.handle(error) }
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        error = error.message,
                    )
                }
            }
        }
    }

    private fun loadProducts(successMessage: String? = null, selectedProductId: Int? = _uiState.value.selectedProductId) {
        val snapshot = _uiState.value
        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isLoading = true,
                    error = null,
                    successMessage = successMessage,
                )
            }

            runCatching {
                planningRepository.hppPriority(
                    search = snapshot.searchQuery.trim(),
                    month = null,
                    limit = null,
                )
            }.onSuccess { data ->
                applyHppData(
                    data = data,
                    successMessage = successMessage,
                    selectedProductId = selectedProductId,
                )
            }.onFailure { throwable ->
                val error = throwable.toAppUiError("Terjadi kesalahan saat memuat Quick HPP.")
                viewModelScope.launch { sessionCoordinator.handle(error) }
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        isSaving = false,
                        error = error.message,
                    )
                }
            }
        }
    }

    private fun applyHppData(
        data: HppPriorityData,
        successMessage: String? = null,
        selectedProductId: Int? = null,
    ) {
        _uiState.update { current ->
            val products = data.products.map { product ->
                val variants = product.variants.orEmpty()
                HppEditorItem(
                    id = product.id,
                    name = product.name,
                    sku = product.sku.orEmpty().ifBlank { "-" },
                    category = product.category.orEmpty().ifBlank { "-" },
                    basePrice = product.basePrice,
                    isPriority = product.isPriority,
                    missingHpp = product.missingHpp,
                    hppAmountInput = product.hppAmount.toDigitsInput(),
                    packagingType = product.packagingType,
                    packagingValueInput = product.packagingValue.toDigitsInput(),
                    originalHppAmountInput = product.hppAmount.toDigitsInput(),
                    originalPackagingType = product.packagingType,
                    originalPackagingValueInput = product.packagingValue.toDigitsInput(),
                    variants = variants.map { variant ->
                        HppEditorVariantItem(
                            id = variant.id,
                            name = variant.name,
                            sku = variant.sku.orEmpty().ifBlank { "-" },
                            basePrice = variant.basePrice,
                            hppAmountInput = variant.hppAmount.toDigitsInput(),
                            packagingType = variant.packagingType,
                            packagingValueInput = variant.packagingValue.toDigitsInput(),
                            originalHppAmountInput = variant.hppAmount.toDigitsInput(),
                            originalPackagingType = variant.packagingType,
                            originalPackagingValueInput = variant.packagingValue.toDigitsInput(),
                            effectiveHppAmount = variant.effectiveHppAmount,
                            effectivePackagingType = variant.effectivePackagingType,
                            effectivePackagingValue = variant.effectivePackagingValue,
                            inheritsProduct = variant.inheritsProduct,
                            missingHpp = variant.missingHpp,
                            effectiveMissingHpp = variant.effectiveMissingHpp,
                        )
                    },
                )
            }

            current.copy(
                isLoading = false,
                isSaving = false,
                error = null,
                successMessage = successMessage,
                shopLabel = data.shop.label,
                summary = data.summary,
                products = products,
                selectedProductId = selectedProductId?.takeIf { id -> products.any { it.id == id } },
            )
        }
    }

    private fun updateProduct(id: Int, transform: (HppEditorItem) -> HppEditorItem) {
        _uiState.update { current ->
            current.copy(
                error = null,
                successMessage = null,
                products = current.products.map { item ->
                    if (item.id == id) transform(item) else item
                },
            )
        }
    }
}

val HppUiState.selectedProduct: HppEditorItem?
    get() = products.firstOrNull { it.id == selectedProductId }

fun HppUiState.visibleProducts(): List<HppEditorItem> = products.filteredBy(selectedFilter)

fun HppUiState.draftCount(): Int = products.count { it.hasChanges() }

private fun List<HppEditorItem>.filteredBy(filter: HppQuickFilter): List<HppEditorItem> {
    return when (filter) {
        HppQuickFilter.NeedHpp -> filter { it.missingHpp }
        HppQuickFilter.Priority -> filter { it.isPriority }
        HppQuickFilter.Ready -> filter { !it.missingHpp }
        HppQuickFilter.All -> this
    }
}

private fun nextProductId(state: HppUiState): Int? {
    val visible = state.visibleProducts()
    val selectedId = state.selectedProductId ?: return visible.firstOrNull()?.id
    val currentIndex = visible.indexOfFirst { it.id == selectedId }
    return when {
        visible.isEmpty() -> null
        currentIndex == -1 -> visible.firstOrNull()?.id
        currentIndex + 1 < visible.size -> visible[currentIndex + 1].id
        else -> null
    }
}

fun HppEditorItem.hasChanges(): Boolean {
    return hppAmountInput.toIntField() != originalHppAmountInput.toIntField() ||
        packagingValueInput.toIntField() != originalPackagingValueInput.toIntField() ||
        packagingType != originalPackagingType ||
        variants.any { it.hasChanges() }
}

private fun HppEditorVariantItem.hasChanges(): Boolean {
    return hppAmountInput.toIntField() != originalHppAmountInput.toIntField() ||
        packagingValueInput.toIntField() != originalPackagingValueInput.toIntField() ||
        packagingType != originalPackagingType
}

private fun HppEditorItem.toSaveInputOrNull(): SaveHppProductInput? {
    val currentHpp = hppAmountInput.toIntField()
    val currentPackagingValue = packagingValueInput.toIntField()
    val currentPackagingType = packagingType

    val productChanged = currentHpp != originalHppAmountInput.toIntField() ||
        currentPackagingValue != originalPackagingValueInput.toIntField() ||
        currentPackagingType != originalPackagingType

    val variantPayloads = variants.mapNotNull { variant ->
        if (!variant.hasChanges()) {
            null
        } else {
            SaveHppVariantInput(
                id = variant.id,
                hppAmount = variant.hppAmountInput.toIntField(),
                packagingType = variant.packagingType,
                packagingValue = variant.packagingValueInput.toIntField(),
            )
        }
    }

    if (!productChanged && variantPayloads.isEmpty()) return null

    return SaveHppProductInput(
        id = id,
        hppAmount = currentHpp,
        packagingType = currentPackagingType,
        packagingValue = currentPackagingValue,
        variants = variantPayloads,
    )
}

private fun String.toIntField(): Int? {
    val digits = replace(Regex("[^0-9]"), "")
    return digits.toIntOrNull()
}

private fun String.toDigitsInput(): String = replace(Regex("[^0-9]"), "")

private fun Int?.toDigitsInput(): String = this?.toString().orEmpty()
