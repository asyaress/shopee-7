package com.toedjoe.android7.data.local

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.booleanPreferencesKey
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.intPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.toedjoe.android7.data.remote.LoginData
import com.toedjoe.android7.data.remote.MeData
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map

private val Context.sessionDataStore: DataStore<Preferences> by preferencesDataStore(name = "mobile_session")

data class AppSession(
    val token: String? = null,
    val userId: Int? = null,
    val userName: String? = null,
    val userEmail: String? = null,
    val activeShopId: Int? = null,
    val hasSeenWelcome: Boolean = false,
)

@Singleton
class SessionStore @Inject constructor(
    @param:ApplicationContext private val context: Context,
) {
    private val tokenKey = stringPreferencesKey("token")
    private val userIdKey = intPreferencesKey("user_id")
    private val userNameKey = stringPreferencesKey("user_name")
    private val userEmailKey = stringPreferencesKey("user_email")
    private val activeShopIdKey = intPreferencesKey("active_shop_id")
    private val hasSeenWelcomeKey = booleanPreferencesKey("has_seen_welcome")

    val session: Flow<AppSession> = context.sessionDataStore.data.map { prefs ->
        AppSession(
            token = prefs[tokenKey],
            userId = prefs[userIdKey],
            userName = prefs[userNameKey],
            userEmail = prefs[userEmailKey],
            activeShopId = prefs[activeShopIdKey],
            hasSeenWelcome = prefs[hasSeenWelcomeKey] ?: false,
        )
    }

    suspend fun authToken(): String? = session.map { it.token }.firstOrNull()

    suspend fun saveLogin(data: LoginData) {
        context.sessionDataStore.edit { prefs ->
            prefs[tokenKey] = data.token
            prefs[userIdKey] = data.user.id
            prefs[userNameKey] = data.user.name
            prefs[userEmailKey] = data.user.email
            data.activeShopId?.let { prefs[activeShopIdKey] = it }
        }
    }

    suspend fun updateProfile(data: MeData) {
        context.sessionDataStore.edit { prefs ->
            prefs[userIdKey] = data.user.id
            prefs[userNameKey] = data.user.name
            prefs[userEmailKey] = data.user.email
            data.activeShopId?.let { prefs[activeShopIdKey] = it }
        }
    }

    suspend fun updateActiveShop(activeShopId: Int?) {
        context.sessionDataStore.edit { prefs ->
            if (activeShopId == null) {
                prefs.remove(activeShopIdKey)
            } else {
                prefs[activeShopIdKey] = activeShopId
            }
        }
    }

    suspend fun markWelcomeSeen() {
        context.sessionDataStore.edit { prefs ->
            prefs[hasSeenWelcomeKey] = true
        }
    }

    suspend fun clear() {
        context.sessionDataStore.edit { prefs ->
            val hasSeenWelcome = prefs[hasSeenWelcomeKey] ?: false
            prefs.clear()
            if (hasSeenWelcome) {
                prefs[hasSeenWelcomeKey] = true
            }
        }
    }
}
