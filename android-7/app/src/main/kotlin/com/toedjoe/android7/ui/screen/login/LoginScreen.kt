package com.toedjoe.android7.ui.screen.login

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.rounded.Email
import androidx.compose.material.icons.rounded.Lock
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.toedjoe.android7.BuildConfig
import com.toedjoe.android7.ui.component.AuthScaffold
import com.toedjoe.android7.ui.component.BrandBadge
import com.toedjoe.android7.ui.theme.Clay900
import com.toedjoe.android7.ui.theme.Ember600

@Composable
fun LoginScreen(
    viewModel: LoginViewModel,
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    AuthScaffold(
        title = "Sign in",
        subtitle = "Masuk dengan akun server Anda untuk membuka KPI, analytics, dan quick action toko.",
    ) {
        BrandBadge(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 6.dp),
            compact = true,
        )
        OutlinedTextField(
            value = uiState.email,
            onValueChange = viewModel::updateEmail,
            label = { Text("Email") },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            leadingIcon = {
                androidx.compose.material3.Icon(
                    imageVector = Icons.Rounded.Email,
                    contentDescription = "Email",
                )
            },
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
        )
        OutlinedTextField(
            value = uiState.password,
            onValueChange = viewModel::updatePassword,
            label = { Text("Password") },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true,
            leadingIcon = {
                androidx.compose.material3.Icon(
                    imageVector = Icons.Rounded.Lock,
                    contentDescription = "Password",
                )
            },
            visualTransformation = PasswordVisualTransformation(),
        )
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
        ) {
            Text(
                text = "Endpoint: ${BuildConfig.API_LABEL}",
                style = MaterialTheme.typography.bodySmall,
                color = Clay900.copy(alpha = 0.56f),
            )
            Text(
                text = "Secure login",
                style = MaterialTheme.typography.bodySmall,
                color = Ember600,
                fontWeight = FontWeight.SemiBold,
            )
        }
        uiState.error?.takeIf { it.isNotBlank() }?.let { error ->
            Text(
                text = error,
                color = MaterialTheme.colorScheme.error,
                style = MaterialTheme.typography.bodySmall,
            )
        }
        Button(
            onClick = viewModel::login,
            enabled = !uiState.isLoading,
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 8.dp)
                .height(56.dp),
        ) {
            if (uiState.isLoading) {
                CircularProgressIndicator(
                    color = Color.White,
                    strokeWidth = 2.dp,
                )
            } else {
                Text("Login", fontWeight = FontWeight.Bold)
            }
        }
        TextButton(
            onClick = {},
            enabled = false,
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text("Gunakan akun server yang aktif di sistem")
        }
    }
}
