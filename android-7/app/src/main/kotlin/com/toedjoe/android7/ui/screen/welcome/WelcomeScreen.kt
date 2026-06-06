package com.toedjoe.android7.ui.screen.welcome

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.rounded.ArrowForward
import androidx.compose.material3.Button
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.toedjoe.android7.ui.component.AuthScaffold
import com.toedjoe.android7.ui.component.BrandBadge
import com.toedjoe.android7.ui.component.WelcomeMetricChip
import com.toedjoe.android7.ui.theme.Clay900

@Composable
fun WelcomeScreen(
    onContinue: () -> Unit,
) {
    AuthScaffold(
        title = "Welcome",
        subtitle = "Pantau profit, iklan, target, dan quick action toko dari satu mobile cockpit yang ringan.",
    ) {
        BrandBadge(modifier = Modifier.fillMaxWidth())
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            WelcomeMetricChip(label = "CEO class")
            WelcomeMetricChip(label = "Realtime KPI")
            WelcomeMetricChip(label = "Quick HPP")
        }
        Text(
            text = "Masuk ke dashboard yang dirancang untuk keputusan cepat: lihat angka inti, buka analytics, dan benahi item penting tanpa harus buka laptop.",
            style = MaterialTheme.typography.bodyMedium,
            color = Clay900.copy(alpha = 0.72f),
        )
        Button(
            onClick = onContinue,
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 8.dp)
                .height(56.dp),
        ) {
            Text("Continue", fontWeight = FontWeight.Bold)
            Icon(
                imageVector = Icons.Rounded.ArrowForward,
                contentDescription = "Continue",
                modifier = Modifier.padding(start = 8.dp),
            )
        }
    }
}
