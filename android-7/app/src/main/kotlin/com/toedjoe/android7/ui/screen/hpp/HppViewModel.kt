package com.toedjoe.android7.ui.screen.hpp

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.toedjoe.android7.data.remote.HppPriorityData
import com.toedjoe.android7.data.remote.HppPrioritySummary
import com.toedjoe.android7.data.remote.SaveHppProductInput
import com.toedjoe.android7.data.repository.PlanningRepository
import com.toedjoe.android7.support.SessionCoordinator
import com.toedjoe.android7.support.toAppUiError
import dagger.hilt.android.lifecycle.HiltViewModel
import java.text.DecimalFormat
import java.text.DecimalFormatSymbols
import java.util.Locale
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
        it.copy(hppAmountInput = value.toNominalInput())
    }

    fun updatePackagingType(id: Int, value: String) = updateProduct(id) {
        it.copy(
            packagingType = value,
            packagingValueInput = it.packagingValueInput.reformatByPackagingType(value),
        )
    }

    fun updatePackagingValue(id: Int, value: String) = updateProduct(id) {
        it.copy(packagingValueInput = value.reformatByPackagingType(it.packagingType))
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

        if (payload == null) {
            _uiState.update {
                it.copy(
                    successMessage = "Belum ada perubahan untuk disimpan.",
                    selectedProductId = if (moveNext) nextProductId(it) else it.selectedProductId,
                    error = null,
                )
            }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, error = null, successMessage = null) }

            runCatching {
                planningRepository.saveHpp(listOf(payload))
            }.onSuccess { result ->
                _uiState.update { current ->
                    val before = current.products.firstOrNull { it.id == selected.id } ?: selected
                    val updatedProducts = current.products.map { item ->
                        if (item.id != selected.id) {
                            item
                        } else {
                            item.copy(
                                missingHpp = item.hppAmountInput.toIntField() == null,
                                originalHppAmountInput = item.hppAmountInput,
                                originalPackagingType = item.packagingType,
                                originalPackagingValueInput = item.packagingValueInput,
                            )
                        }
                    }
                    val after = updatedProducts.firstOrNull { it.id == selected.id } ?: before
                    val nextId = if (moveNext) nextProductId(
                        current.copy(products = updatedProducts, selectedProductId = selected.id),
                    ) else selected.id

                    current.copy(
                        isSaving = false,
                        error = null,
                        successMessage = result.message,
                        products = updatedProducts,
                        summary = current.summary.updatedWith(before = before, after = after),
                        selectedProductId = nextId,
                    )
                }
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

    private fun loadProducts(successMessage: String? = null) {
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
                applyHppData(data, successMessage = successMessage)
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
    ) {
        _uiState.update { current ->
            val products = data.products.map { product ->
                HppEditorItem(
                    id = product.id,
                    name = product.name,
                    sku = product.sku.orEmpty().ifBlank { "-" },
                    category = product.category.orEmpty().ifBlank { "-" },
                    basePrice = product.basePrice,
                    isPriority = product.isPriority,
                    missingHpp = product.missingHpp,
                    hppAmountInput = product.hppAmount.toNominalInput(),
                    packagingType = product.packagingType,
                    packagingValueInput = product.packagingValue.toPackagingInput(product.packagingType),
                    originalHppAmountInput = product.hppAmount.toNominalInput(),
                    originalPackagingType = product.packagingType,
                    originalPackagingValueInput = product.packagingValue.toPackagingInput(product.packagingType),
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
                selectedProductId = current.selectedProductId?.takeIf { id -> products.any { it.id == id } },
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
        packagingType != originalPackagingType
}

private fun HppEditorItem.toSaveInputOrNull(): SaveHppProductInput? {
    val currentHpp = hppAmountInput.toIntField()
    val currentPackagingValue = packagingValueInput.toIntField()
    val currentPackagingType = packagingType

    val isChanged = currentHpp != originalHppAmountInput.toIntField() ||
        currentPackagingValue != originalPackagingValueInput.toIntField() ||
        currentPackagingType != originalPackagingType

    if (!isChanged) return null

    return SaveHppProductInput(
        id = id,
        hppAmount = currentHpp,
        packagingType = currentPackagingType,
        packagingValue = currentPackagingValue,
    )
}

private fun HppPrioritySummary.updatedWith(
    before: HppEditorItem,
    after: HppEditorItem,
): HppPrioritySummary {
    val beforeMissing = before.hppAmountInput.toIntField() == null
    val afterMissing = after.hppAmountInput.toIntField() == null
    if (beforeMissing == afterMissing) return this

    val nextWithHpp = if (afterMissing) (withHpp - 1).coerceAtLeast(0) else withHpp + 1
    val nextMissing = if (afterMissing) missing + 1 else (missing - 1).coerceAtLeast(0)

    return copy(
        withHpp = nextWithHpp,
        missing = nextMissing,
        completePct = if (total > 0) nextWithHpp.toDouble() / total.toDouble() else completePct,
    )
}

private fun String.toIntField(): Int? {
    val digits = replace(Regex("[^0-9]"), "")
    return digits.toIntOrNull()
}

private fun String.toNominalInput(): String = toIntField().formatNominalInput()

private fun String.reformatByPackagingType(type: String): String {
    return when (type) {
        "percent" -> toIntField()?.toString().orEmpty()
        else -> toIntField().formatNominalInput()
    }
}

private fun Int?.toNominalInput(): String = formatNominalInput()

private fun Int?.toPackagingInput(type: String): String {
    return when (type) {
        "percent" -> this?.toString().orEmpty()
        else -> formatNominalInput()
    }
}

private fun Int?.formatNominalInput(): String {
    val value = this ?: return ""
    return integerInputFormatter.format(value)
}

private val integerInputFormatter = DecimalFormat(
    "#,###",
    DecimalFormatSymbols(Locale.forLanguageTag("id-ID")).apply {
        groupingSeparator = '.'
        decimalSeparator = ','
    },
)
