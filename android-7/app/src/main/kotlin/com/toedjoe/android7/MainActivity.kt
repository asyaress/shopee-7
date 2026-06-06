package com.toedjoe.android7

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.enableEdgeToEdge
import androidx.activity.compose.setContent
import androidx.core.splashscreen.SplashScreen.Companion.installSplashScreen
import androidx.compose.runtime.getValue
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.toedjoe.android7.ui.navigation.AppNavHost
import com.toedjoe.android7.ui.theme.Android7Theme
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        installSplashScreen()
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        setContent {
            Android7Theme {
                val viewModel: MainViewModel = hiltViewModel()
                val session by viewModel.session.collectAsStateWithLifecycle()
                AppNavHost(
                    isLoggedIn = session.token.isNullOrBlank().not(),
                    hasSeenWelcome = session.hasSeenWelcome,
                    onCompleteWelcome = viewModel::completeWelcome,
                )
            }
        }
    }
}
