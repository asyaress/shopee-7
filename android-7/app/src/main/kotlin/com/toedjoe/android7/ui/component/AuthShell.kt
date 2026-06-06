package com.toedjoe.android7.ui.component

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.toedjoe.android7.R
import com.toedjoe.android7.ui.theme.Clay50
import com.toedjoe.android7.ui.theme.Clay900
import com.toedjoe.android7.ui.theme.Ember50
import com.toedjoe.android7.ui.theme.Ember600

@Composable
fun AuthScaffold(
    modifier: Modifier = Modifier,
    title: String,
    subtitle: String,
    content: @Composable ColumnScope.() -> Unit,
) {
    Box(
        modifier = modifier
            .fillMaxSize()
            .background(Brush.verticalGradient(colors = listOf(Color(0xFFF39A90), Color(0xFFF08C83)))),
    ) {
        AuthPatternBackground()
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .align(Alignment.BottomCenter)
                .clip(RoundedCornerShape(topStart = 42.dp, topEnd = 42.dp))
                .background(Clay50)
                .padding(horizontal = 24.dp, vertical = 28.dp),
        ) {
            Column(
                verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                Text(
                    text = title,
                    style = MaterialTheme.typography.headlineLarge,
                    fontWeight = FontWeight.ExtraBold,
                    color = Clay900,
                )
                Surface(
                    color = Ember600,
                    shape = RoundedCornerShape(999.dp),
                    modifier = Modifier.size(width = 58.dp, height = 4.dp),
                ) {}
                Text(
                    text = subtitle,
                    style = MaterialTheme.typography.bodyMedium,
                    color = Clay900.copy(alpha = 0.66f),
                )
                content()
            }
        }
    }
}

@Composable
fun BrandBadge(
    modifier: Modifier = Modifier,
    compact: Boolean = false,
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(if (compact) 22.dp else 28.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White.copy(alpha = 0.16f)),
    ) {
        Row(
            modifier = Modifier.padding(horizontal = if (compact) 14.dp else 18.dp, vertical = if (compact) 12.dp else 16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Image(
                painter = painterResource(id = R.drawable.logo_7sinar),
                contentDescription = "7 Sinar logo",
                modifier = Modifier
                    .size(if (compact) 46.dp else 64.dp)
                    .clip(RoundedCornerShape(if (compact) 14.dp else 18.dp)),
            )
            Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                Text(
                    text = "7 CEO Mobile",
                    style = if (compact) MaterialTheme.typography.titleMedium else MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                    color = Color.White,
                )
                Text(
                    text = "Shopee monitoring cockpit",
                    style = MaterialTheme.typography.bodySmall,
                    color = Color.White.copy(alpha = 0.78f),
                )
            }
        }
    }
}

@Composable
fun WelcomeMetricChip(
    label: String,
    modifier: Modifier = Modifier,
) {
    Surface(
        modifier = modifier,
        color = Ember50.copy(alpha = 0.9f),
        shape = RoundedCornerShape(999.dp),
    ) {
        Text(
            text = label,
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
            style = MaterialTheme.typography.labelMedium,
            color = Ember600,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

@Composable
private fun AuthPatternBackground() {
    androidx.compose.foundation.Canvas(
        modifier = Modifier
            .fillMaxWidth()
            .height(430.dp),
    ) {
        repeat(9) { index ->
            val shift = index * 96f
            val path = Path().apply {
                moveTo(-80f + shift, 24f)
                cubicTo(60f + shift, 40f, 10f + shift, 180f, 120f + shift, 210f)
                cubicTo(210f + shift, 250f, 180f + shift, 360f, 70f + shift, 390f)
                cubicTo(-20f + shift, 420f, -90f + shift, 320f, -10f + shift, 250f)
                cubicTo(70f + shift, 190f, 20f + shift, 90f, -80f + shift, 24f)
                close()
            }
            drawPath(
                path = path,
                color = Color.White.copy(alpha = if (index % 2 == 0) 0.16f else 0.10f),
                style = Stroke(width = 3f),
            )
        }

        val wave = Path().apply {
            moveTo(0f, size.height * 0.73f)
            cubicTo(
                x1 = size.width * 0.22f,
                y1 = size.height * 0.62f,
                x2 = size.width * 0.38f,
                y2 = size.height * 0.90f,
                x3 = size.width * 0.58f,
                y3 = size.height * 0.84f,
            )
            cubicTo(
                x1 = size.width * 0.74f,
                y1 = size.height * 0.78f,
                x2 = size.width * 0.86f,
                y2 = size.height * 0.98f,
                x3 = size.width,
                y3 = size.height * 0.82f,
            )
            lineTo(size.width, size.height)
            lineTo(0f, size.height)
            close()
        }
        drawPath(
            path = wave,
            brush = Brush.verticalGradient(
                colors = listOf(Color.White.copy(alpha = 0.22f), Color.White.copy(alpha = 0.06f)),
                startY = size.height * 0.6f,
                endY = size.height,
            ),
        )

        drawCircle(
            color = Color.White.copy(alpha = 0.14f),
            radius = 88f,
            center = Offset(size.width * 0.82f, size.height * 0.22f),
        )
        drawCircle(
            color = Color.White.copy(alpha = 0.08f),
            radius = 122f,
            center = Offset(size.width * 0.18f, size.height * 0.18f),
        )
    }
}
