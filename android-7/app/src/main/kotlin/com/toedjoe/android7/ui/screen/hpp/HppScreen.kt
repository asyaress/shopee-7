package com.toedjoe.android7.ui.screen.hpp

import androidx.compose.animation.animateContentSize
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.OffsetMapping
import androidx.compose.ui.text.input.TransformedText
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.toedjoe.android7.ui.theme.Aqua50
import com.toedjoe.android7.ui.theme.Aqua700
import com.toedjoe.android7.ui.theme.Clay100
import com.toedjoe.android7.ui.theme.Clay300
import com.toedjoe.android7.ui.theme.Clay50
import com.toedjoe.android7.ui.theme.Clay700
import com.toedjoe.android7.ui.theme.Clay900
import com.toedjoe.android7.ui.theme.Ember50
import com.toedjoe.android7.ui.theme.Ember600
import com.toedjoe.android7.ui.theme.Pine50
import com.toedjoe.android7.ui.theme.Pine600
import com.toedjoe.android7.ui.util.formatCurrency
import com.toedjoe.android7.ui.util.formatPercent
import com.toedjoe.android7.ui.util.formatWholeNumber

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HppScreen(
    viewModel: HppViewModel,
    onBack: (() -> Unit)? = null,
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val visibleProducts = uiState.visibleProducts()
    val selectedProduct = uiState.selectedProduct

    if (selectedProduct != null) {
        ModalBottomSheet(
            onDismissRequest = viewModel::closeEditor,
            containerColor = MaterialTheme.colorScheme.surface,
        ) {
            QuickHppEditorSheet(
                product = selectedProduct,
                isSaving = uiState.isSaving,
                onClose = viewModel::closeEditor,
                onSkip = viewModel::skipToNext,
                onHppChange = { viewModel.updateHppAmount(selectedProduct.id, it) },
                onPackagingTypeChange = { viewModel.updatePackagingType(selectedProduct.id, it) },
                onPackagingValueChange = { viewModel.updatePackagingValue(selectedProduct.id, it) },
                onVariantHppChange = { variantId, value ->
                    viewModel.updateVariantHppAmount(selectedProduct.id, variantId, value)
                },
                onVariantPackagingTypeChange = { variantId, value ->
                    viewModel.updateVariantPackagingType(selectedProduct.id, variantId, value)
                },
                onVariantPackagingValueChange = { variantId, value ->
                    viewModel.updateVariantPackagingValue(selectedProduct.id, variantId, value)
                },
                onSave = viewModel::saveSelected,
                onSaveNext = viewModel::saveSelectedAndNext,
            )
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Quick HPP Fix", fontWeight = FontWeight.Bold)
                        if (uiState.shopLabel.isNotBlank()) {
                            Text(uiState.shopLabel, style = MaterialTheme.typography.labelSmall)
                        }
                    }
                },
                navigationIcon = {
                    if (onBack != null) {
                        TextButton(onClick = onBack) {
                            Text("Back")
                        }
                    }
                },
                actions = {
                    TextButton(onClick = viewModel::refresh) {
                        Text("Refresh")
                    }
                },
            )
        },
    ) { innerPadding ->
        if (uiState.isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding),
                contentAlignment = Alignment.Center,
            ) {
                CircularProgressIndicator()
            }
        } else {
            androidx.compose.foundation.lazy.LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .background(Clay50)
                    .padding(innerPadding),
                verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                item {
                    HppHeroCard(
                        uiState = uiState,
                        visibleCount = visibleProducts.size,
                    )
                }
                item {
                    SectionCard(
                        title = "All products queue",
                        subtitle = "Semua produk tampil penuh. Untuk produk bervariant, editor akan membuka input default produk dan override per variant.",
                    ) {
                        OutlinedTextField(
                            value = uiState.searchQuery,
                            onValueChange = viewModel::updateSearchQuery,
                            label = { Text("Cari nama / SKU / kategori") },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true,
                        )
                        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                            HppQuickFilter.entries.forEach { filter ->
                                FilterChip(
                                    selected = filter == uiState.selectedFilter,
                                    onClick = { viewModel.selectFilter(filter) },
                                    label = { Text(filter.label) },
                                )
                            }
                        }
                        Button(onClick = viewModel::search) {
                            Text("Refresh queue")
                        }
                    }
                }
                uiState.error?.takeIf { it.isNotBlank() }?.let { error ->
                    item {
                        FeedbackCard(
                            title = "Error",
                            text = error,
                            containerColor = MaterialTheme.colorScheme.errorContainer,
                            textColor = MaterialTheme.colorScheme.onErrorContainer,
                        )
                    }
                }
                uiState.successMessage?.takeIf { it.isNotBlank() }?.let { message ->
                    item {
                        FeedbackCard(
                            title = "Saved",
                            text = message,
                            containerColor = Pine50,
                            textColor = MaterialTheme.colorScheme.onSurface,
                        )
                    }
                }
                if (visibleProducts.isEmpty()) {
                    item {
                        EmptyQueueCard(filter = uiState.selectedFilter)
                    }
                } else {
                    items(visibleProducts, key = { it.id }) { product ->
                        QuickHppListCard(
                            product = product,
                            onOpen = { viewModel.openEditor(product.id) },
                        )
                    }
                }
                item {
                    Spacer(modifier = Modifier.height(20.dp))
                }
            }
        }
    }
}

@Composable
private fun HppHeroCard(
    uiState: HppUiState,
    visibleCount: Int,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 12.dp),
        colors = CardDefaults.cardColors(containerColor = Clay900),
    ) {
        Column(
            modifier = Modifier
                .padding(18.dp)
                .animateContentSize(),
            verticalArrangement = Arrangement.spacedBy(14.dp),
        ) {
            Text(
                text = "HPP action desk",
                style = MaterialTheme.typography.labelLarge,
                color = Color.White.copy(alpha = 0.80f),
            )
            Text(
                text = "Sekarang queue mobile juga membaca variant. Default produk tetap ada, tapi item yang punya variant bisa langsung dipecah per variant dari editor yang sama.",
                style = MaterialTheme.typography.bodyMedium,
                color = Color.White.copy(alpha = 0.88f),
            )
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                HeroMetric(
                    modifier = Modifier.weight(1f),
                    title = "Coverage",
                    value = formatPercent(uiState.summary.completePct),
                )
                HeroMetric(
                    modifier = Modifier.weight(1f),
                    title = "Need HPP",
                    value = formatWholeNumber(uiState.summary.missing),
                )
                HeroMetric(
                    modifier = Modifier.weight(1f),
                    title = "Draft",
                    value = formatWholeNumber(uiState.draftCount()),
                )
            }
            Surface(
                color = Color.White.copy(alpha = 0.08f),
                shape = MaterialTheme.shapes.large,
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(14.dp),
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    QueueSpotlight(
                        modifier = Modifier.weight(1f),
                        title = "Visible now",
                        value = formatWholeNumber(visibleCount),
                        accent = Ember600,
                    )
                    QueueSpotlight(
                        modifier = Modifier.weight(1f),
                        title = "Priority mix",
                        value = formatWholeNumber(uiState.products.count { it.isPriority }),
                        accent = Aqua700,
                    )
                    QueueSpotlight(
                        modifier = Modifier.weight(1f),
                        title = "With variants",
                        value = formatWholeNumber(uiState.products.count { it.variants.isNotEmpty() }),
                        accent = Pine600,
                    )
                }
            }
        }
    }
}

@Composable
private fun HeroMetric(
    title: String,
    value: String,
    modifier: Modifier = Modifier,
) {
    Surface(
        modifier = modifier,
        color = Color.White.copy(alpha = 0.10f),
        shape = MaterialTheme.shapes.medium,
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(4.dp),
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.labelSmall,
                color = Color.White.copy(alpha = 0.76f),
            )
            Text(
                text = value,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = Color.White,
            )
        }
    }
}

@Composable
private fun QueueSpotlight(
    title: String,
    value: String,
    accent: Color,
    modifier: Modifier = Modifier,
) {
    Surface(
        modifier = modifier,
        color = Color.Transparent,
    ) {
        Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
            Box(
                modifier = Modifier
                    .height(4.dp)
                    .fillMaxWidth()
                    .background(
                        brush = Brush.horizontalGradient(
                            colors = listOf(accent, accent.copy(alpha = 0.18f)),
                        ),
                        shape = RoundedCornerShape(999.dp),
                    ),
            )
            Text(
                text = title,
                style = MaterialTheme.typography.labelSmall,
                color = Color.White.copy(alpha = 0.74f),
            )
            Text(
                text = value,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = Color.White,
            )
        }
    }
}

@Composable
private fun QuickHppListCard(
    product: HppEditorItem,
    onOpen: () -> Unit,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp)
            .clickable(onClick = onOpen),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        border = BorderStroke(1.dp, Clay300.copy(alpha = 0.45f)),
    ) {
        Column(
            modifier = Modifier
                .padding(16.dp)
                .animateContentSize(),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top,
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = product.name,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                    )
                    Text(
                        text = "${product.category} | ${product.sku}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    if (product.variants.isNotEmpty()) {
                        Text(
                            text = product.variantSummaryLine(),
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
                Spacer(modifier = Modifier.width(12.dp))
                TextButton(onClick = onOpen) {
                    Text("Fix now")
                }
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                if (product.missingHpp) {
                    StatusPill(label = "Need HPP", containerColor = MaterialTheme.colorScheme.errorContainer)
                }
                if (product.isPriority) {
                    StatusPill(label = "Priority", containerColor = Ember50)
                }
                if (product.variants.isNotEmpty()) {
                    StatusPill(label = "${product.variants.size} variants", containerColor = Clay100)
                }
                if (product.hasChanges()) {
                    StatusPill(label = "Draft", containerColor = Aqua50)
                }
            }
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                MiniMetric(
                    modifier = Modifier.weight(1f),
                    title = "HPP",
                    value = product.hppAmountInput.toCurrencyOrDash(),
                )
                MiniMetric(
                    modifier = Modifier.weight(1f),
                    title = "Packing",
                    value = product.packagingDisplay(),
                )
                MiniMetric(
                    modifier = Modifier.weight(1f),
                    title = "Price",
                    value = product.basePrice?.let { formatCurrency(it.toDouble()) } ?: "-",
                )
            }
        }
    }
}

@Composable
private fun QuickHppEditorSheet(
    product: HppEditorItem,
    isSaving: Boolean,
    onClose: () -> Unit,
    onSkip: () -> Unit,
    onHppChange: (String) -> Unit,
    onPackagingTypeChange: (String) -> Unit,
    onPackagingValueChange: (String) -> Unit,
    onVariantHppChange: (Int, String) -> Unit,
    onVariantPackagingTypeChange: (Int, String?) -> Unit,
    onVariantPackagingValueChange: (Int, String) -> Unit,
    onSave: () -> Unit,
    onSaveNext: () -> Unit,
) {
    val scrollState = rememberScrollState()

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .verticalScroll(scrollState)
            .padding(horizontal = 20.dp, vertical = 4.dp)
            .animateContentSize(),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
            Text(
                text = "Quick fix editor",
                style = MaterialTheme.typography.labelLarge,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Text(
                text = product.name,
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.Bold,
            )
            Text(
                text = "${product.category} | ${product.sku}",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }

        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            if (product.missingHpp) {
                StatusPill(label = "Need HPP", containerColor = MaterialTheme.colorScheme.errorContainer)
            }
            if (product.isPriority) {
                StatusPill(label = "Priority queue", containerColor = Ember50)
            }
            if (product.variants.isNotEmpty()) {
                StatusPill(label = "${product.variants.size} variants", containerColor = Clay100)
            }
            if (product.hasChanges()) {
                StatusPill(label = "Draft ready", containerColor = Aqua50)
            }
        }

        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            MiniMetric(
                modifier = Modifier.weight(1f),
                title = "Current price",
                value = product.basePrice?.let { formatCurrency(it.toDouble()) } ?: "-",
            )
            MiniMetric(
                modifier = Modifier.weight(1f),
                title = "Default HPP",
                value = product.hppAmountInput.toCurrencyOrDash(),
            )
        }

        SectionCard(
            title = "Default produk",
            subtitle = "Nilai ini akan jadi fallback untuk variant yang dibiarkan kosong.",
        ) {
            ProductDefaultEditor(
                product = product,
                onHppChange = onHppChange,
                onPackagingTypeChange = onPackagingTypeChange,
                onPackagingValueChange = onPackagingValueChange,
            )
        }

        if (product.variants.isNotEmpty()) {
            SectionCard(
                title = "Biaya per Variant",
                subtitle = "Isi hanya variant yang butuh override. Kalau dibiarkan kosong, variant akan ikut default produk.",
            ) {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    product.variants.forEach { variant ->
                        VariantEditorCard(
                            variant = variant,
                            onHppChange = { onVariantHppChange(variant.id, it) },
                            onPackagingTypeChange = { onVariantPackagingTypeChange(variant.id, it) },
                            onPackagingValueChange = { onVariantPackagingValueChange(variant.id, it) },
                        )
                    }
                }
            }
        }

        Surface(
            color = Clay100.copy(alpha = 0.7f),
            shape = MaterialTheme.shapes.medium,
        ) {
            Text(
                text = "Alur paling aman: isi default produk dulu, lalu override hanya variant yang memang HPP atau packaging-nya beda.",
                modifier = Modifier.padding(12.dp),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }

        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            TextButton(
                onClick = onClose,
                modifier = Modifier.weight(1f),
            ) {
                Text("Close")
            }
            TextButton(
                onClick = onSkip,
                modifier = Modifier.weight(1f),
            ) {
                Text("Skip")
            }
        }

        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            Button(
                onClick = onSave,
                enabled = !isSaving,
                modifier = Modifier.weight(1f),
                colors = ButtonDefaults.buttonColors(containerColor = Clay700),
            ) {
                if (isSaving) {
                    CircularProgressIndicator(strokeWidth = 2.dp, color = Color.White)
                } else {
                    Text("Save")
                }
            }
            Button(
                onClick = onSaveNext,
                enabled = !isSaving,
                modifier = Modifier.weight(1.2f),
            ) {
                if (isSaving) {
                    CircularProgressIndicator(strokeWidth = 2.dp, color = MaterialTheme.colorScheme.onPrimary)
                } else {
                    Text("Save & Next")
                }
            }
        }
        Spacer(modifier = Modifier.height(12.dp))
    }
}

@Composable
private fun ProductDefaultEditor(
    product: HppEditorItem,
    onHppChange: (String) -> Unit,
    onPackagingTypeChange: (String) -> Unit,
    onPackagingValueChange: (String) -> Unit,
) {
    OutlinedTextField(
        value = product.hppAmountInput,
        onValueChange = onHppChange,
        label = { Text("HPP default") },
        modifier = Modifier.fillMaxWidth(),
        singleLine = true,
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
        prefix = { Text("Rp") },
        visualTransformation = ThousandsSeparatorVisualTransformation,
    )
    PackagingModeChips(
        selectedMode = product.packagingType,
        allowInherit = false,
        onModeSelected = onPackagingTypeChange,
    )
    OutlinedTextField(
        value = product.packagingValueInput,
        onValueChange = onPackagingValueChange,
        label = { Text("Biaya packing default") },
        modifier = Modifier.fillMaxWidth(),
        singleLine = true,
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
        prefix = if (product.packagingType == "fixed") {
            { Text("Rp") }
        } else {
            null
        },
        suffix = if (product.packagingType == "percent") {
            { Text("%") }
        } else {
            null
        },
        visualTransformation = if (product.packagingType == "fixed") {
            ThousandsSeparatorVisualTransformation
        } else {
            VisualTransformation.None
        },
    )
}

@Composable
private fun VariantEditorCard(
    variant: HppEditorVariantItem,
    onHppChange: (String) -> Unit,
    onPackagingTypeChange: (String?) -> Unit,
    onPackagingValueChange: (String) -> Unit,
) {
    val displayMode = variant.packagingType ?: variant.effectivePackagingType
    val effectivePackagingText = if (variant.effectivePackagingType == "percent") {
        variant.effectivePackagingValue?.let { "$it%" } ?: "-"
    } else {
        variant.effectivePackagingValue?.toString()?.toCurrencyOrDash() ?: "-"
    }

    Surface(
        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.26f),
        shape = MaterialTheme.shapes.large,
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp)
                .animateContentSize(),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top,
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = variant.name,
                        style = MaterialTheme.typography.titleSmall,
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        text = buildString {
                            append(variant.sku)
                            if (variant.basePrice != null) {
                                append(" | ")
                                append(formatCurrency(variant.basePrice.toDouble()))
                            }
                        },
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                Spacer(modifier = Modifier.width(8.dp))
                StatusPill(
                    label = when {
                        variant.hasChanges() -> "Draft"
                        variant.packagingType == null && variant.hppAmountInput.isBlank() -> "Inherit"
                        else -> "Override"
                    },
                    containerColor = when {
                        variant.hasChanges() -> Aqua50
                        variant.packagingType == null && variant.hppAmountInput.isBlank() -> Clay100
                        else -> Ember50
                    },
                )
            }

            Text(
                text = "Efektif sekarang: HPP ${variant.effectiveHppAmount?.toString()?.toCurrencyOrDash() ?: "-"} | Packing $effectivePackagingText",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )

            OutlinedTextField(
                value = variant.hppAmountInput,
                onValueChange = onHppChange,
                label = { Text("HPP variant") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                prefix = { Text("Rp") },
                placeholder = { Text("Ikut default produk") },
                visualTransformation = ThousandsSeparatorVisualTransformation,
            )

            PackagingModeChips(
                selectedMode = variant.packagingType,
                allowInherit = true,
                onModeSelected = { mode -> onPackagingTypeChange(mode.takeIf { it.isNotBlank() }) },
            )

            OutlinedTextField(
                value = variant.packagingValueInput,
                onValueChange = onPackagingValueChange,
                enabled = variant.packagingType != null,
                label = { Text("Biaya packing variant") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                placeholder = { Text("Ikut default produk") },
                prefix = if (displayMode == "fixed" && variant.packagingType != null) {
                    { Text("Rp") }
                } else {
                    null
                },
                suffix = if (displayMode == "percent" && variant.packagingType != null) {
                    { Text("%") }
                } else {
                    null
                },
                visualTransformation = if (displayMode == "fixed") {
                    ThousandsSeparatorVisualTransformation
                } else {
                    VisualTransformation.None
                },
            )
        }
    }
}

@Composable
private fun PackagingModeChips(
    selectedMode: String?,
    allowInherit: Boolean,
    onModeSelected: (String) -> Unit,
) {
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text(
            text = "Mode biaya packing",
            style = MaterialTheme.typography.labelLarge,
        )
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            if (allowInherit) {
                FilterChip(
                    selected = selectedMode == null,
                    onClick = { onModeSelected("") },
                    label = { Text("Default") },
                )
            }
            FilterChip(
                selected = selectedMode == "fixed",
                onClick = { onModeSelected("fixed") },
                label = { Text("Fixed") },
            )
            FilterChip(
                selected = selectedMode == "percent",
                onClick = { onModeSelected("percent") },
                label = { Text("Percent") },
            )
        }
    }
}

@Composable
private fun EmptyQueueCard(
    filter: HppQuickFilter,
) {
    SectionCard(
        title = "Queue kosong",
        subtitle = "Tidak ada produk yang cocok dengan filter ${filter.label.lowercase()}.",
    ) {
        Text(
            text = "Coba ganti filter atau pakai pencarian yang lebih umum.",
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
    }
}

@Composable
private fun MiniMetric(
    title: String,
    value: String,
    modifier: Modifier = Modifier,
) {
    Surface(
        modifier = modifier,
        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.42f),
        shape = MaterialTheme.shapes.medium,
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(4.dp),
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Text(
                text = value,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold,
            )
        }
    }
}

@Composable
private fun StatusPill(
    label: String,
    containerColor: Color,
) {
    Surface(
        color = containerColor,
        shape = MaterialTheme.shapes.small,
    ) {
        Text(
            text = label,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
            style = MaterialTheme.typography.labelSmall,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

@Composable
private fun FeedbackCard(
    title: String,
    text: String,
    containerColor: Color,
    textColor: Color,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        colors = CardDefaults.cardColors(containerColor = containerColor),
    ) {
        Column(
            modifier = Modifier.padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp),
        ) {
            Text(title, fontWeight = FontWeight.Bold, color = textColor)
            Text(text, color = textColor)
        }
    }
}

@Composable
private fun SectionCard(
    title: String,
    subtitle: String,
    content: @Composable () -> Unit,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        border = BorderStroke(
            width = 1.dp,
            brush = Brush.linearGradient(
                colors = listOf(Clay300.copy(alpha = 0.42f), Color.Transparent),
            ),
        ),
    ) {
        Column(
            modifier = Modifier
                .padding(16.dp)
                .animateContentSize(),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                Text(
                    text = title,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                )
                Text(
                    text = subtitle,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            content()
        }
    }
}

private fun String.toCurrencyOrDash(): String {
    val value = replace(Regex("[^0-9]"), "").toDoubleOrNull() ?: return "-"
    return formatCurrency(value)
}

private fun HppEditorItem.packagingDisplay(): String {
    if (packagingValueInput.isBlank()) return "-"
    return if (packagingType == "percent") {
        "${packagingValueInput.replace(Regex("[^0-9]"), "")}%"
    } else {
        packagingValueInput.toCurrencyOrDash()
    }
}

private fun HppEditorItem.variantSummaryLine(): String {
    val overrideCount = variants.count { it.hppAmountInput.isNotBlank() || it.packagingType != null }
    val inheritCount = variants.size - overrideCount
    return "${variants.size} variant | $overrideCount override | $inheritCount ikut default"
}

private fun HppEditorVariantItem.hasChanges(): Boolean {
    return hppAmountInput != originalHppAmountInput ||
        packagingType != originalPackagingType ||
        packagingValueInput != originalPackagingValueInput
}

private object ThousandsSeparatorVisualTransformation : VisualTransformation {
    override fun filter(text: AnnotatedString): TransformedText {
        val original = text.text.filter(Char::isDigit)
        if (original.isEmpty()) {
            return TransformedText(AnnotatedString(""), OffsetMapping.Identity)
        }

        val formatted = original
            .reversed()
            .chunked(3)
            .joinToString(".")
            .reversed()

        val originalToTransformed = IntArray(original.length + 1)
        var digitIndex = 0
        formatted.forEachIndexed { index, char ->
            if (char.isDigit()) {
                originalToTransformed[digitIndex + 1] = index + 1
                digitIndex++
            }
        }

        val transformedToOriginal = IntArray(formatted.length + 1)
        var transformedDigitCount = 0
        formatted.forEachIndexed { index, char ->
            if (char.isDigit()) {
                transformedDigitCount++
            }
            transformedToOriginal[index + 1] = transformedDigitCount
        }

        val offsetMapping = object : OffsetMapping {
            override fun originalToTransformed(offset: Int): Int {
                return originalToTransformed[offset.coerceIn(0, original.length)]
            }

            override fun transformedToOriginal(offset: Int): Int {
                return transformedToOriginal[offset.coerceIn(0, formatted.length)]
            }
        }

        return TransformedText(
            text = AnnotatedString(formatted),
            offsetMapping = offsetMapping,
        )
    }
}
