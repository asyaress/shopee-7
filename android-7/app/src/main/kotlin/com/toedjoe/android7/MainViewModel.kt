package com.toedjoe.android7

import android.os.Build
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.toedjoe.android7.data.local.AppSession
import com.toedjoe.android7.data.local.SessionStore
import com.toedjoe.android7.data.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.distinctUntilChangedBy
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch

@HiltViewModel
class MainViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val sessionStore: SessionStore,
) : ViewModel() {
    val session: StateFlow<AppSession> = authRepository.session.stateIn(
        scope = viewModelScope,
        started = SharingStarted.WhileSubscribed(5_000),
        initialValue = AppSession(),
    )

    init {
        observeDeviceRegistration()
    }

    fun completeWelcome() {
        viewModelScope.launch {
            sessionStore.markWelcomeSeen()
        }
    }

    private fun observeDeviceRegistration() {
        viewModelScope.launch {
            authRepository.session
                .distinctUntilChangedBy { it.token }
                .collect { snapshot ->
                    if (snapshot.token.isNullOrBlank()) {
                        return@collect
                    }

                    runCatching {
                        authRepository.registerDevice(
                            platform = "android",
                            deviceName = "CEO Android ${Build.MODEL}",
                            appVersion = BuildConfig.VERSION_NAME,
                            pushToken = null,
                            pushEnabled = false,
                        )
                    }
                }
        }
    }
}
