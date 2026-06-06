package com.toedjoe.android7.ui.navigation

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.spring
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.slideInVertically
import androidx.compose.animation.slideOutVertically
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.rounded.Dashboard
import androidx.compose.material.icons.rounded.EditNote
import androidx.compose.material.icons.rounded.Inventory2
import androidx.compose.material.icons.rounded.Notifications
import androidx.compose.material.icons.rounded.ShowChart
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.remember
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.toedjoe.android7.ui.screen.alerts.AlertsScreen
import com.toedjoe.android7.ui.screen.alerts.AlertsViewModel
import com.toedjoe.android7.ui.screen.dashboard.DashboardScreen
import com.toedjoe.android7.ui.screen.dashboard.DashboardViewModel
import com.toedjoe.android7.ui.screen.decisions.DecisionsScreen
import com.toedjoe.android7.ui.screen.decisions.DecisionsViewModel
import com.toedjoe.android7.ui.screen.hpp.HppScreen
import com.toedjoe.android7.ui.screen.hpp.HppViewModel
import com.toedjoe.android7.ui.screen.login.LoginScreen
import com.toedjoe.android7.ui.screen.login.LoginViewModel
import com.toedjoe.android7.ui.screen.rekap.RekapScreen
import com.toedjoe.android7.ui.screen.rekap.RekapViewModel
import com.toedjoe.android7.ui.screen.targets.TargetsScreen
import com.toedjoe.android7.ui.screen.targets.TargetsViewModel
import com.toedjoe.android7.ui.screen.welcome.WelcomeScreen
import com.toedjoe.android7.ui.theme.Clay300
import com.toedjoe.android7.ui.theme.Clay900

@Composable
fun AppNavHost(
    isLoggedIn: Boolean,
    hasSeenWelcome: Boolean,
    onCompleteWelcome: () -> Unit,
) {
    val navController = rememberNavController()
    val backStackEntry by navController.currentBackStackEntryAsState()
    val currentRoute = backStackEntry?.destination?.route
    val mainDestinations = listOf(
        TopLevelDestination(AppDestination.Dashboard, "Home", Icons.Rounded.Dashboard),
        TopLevelDestination(AppDestination.Rekap, "Analytics", Icons.Rounded.ShowChart),
        TopLevelDestination(AppDestination.Hpp, "HPP", Icons.Rounded.Inventory2),
        TopLevelDestination(AppDestination.Alerts, "Alerts", Icons.Rounded.Notifications),
        TopLevelDestination(AppDestination.Decisions, "Decisions", Icons.Rounded.EditNote),
    )
    val showBottomBar = isLoggedIn && mainDestinations.any { it.destination.route == currentRoute }

    LaunchedEffect(isLoggedIn, hasSeenWelcome) {
        val destination = when {
            isLoggedIn -> AppDestination.Dashboard.route
            !hasSeenWelcome -> AppDestination.Welcome.route
            else -> AppDestination.Login.route
        }

        navController.navigate(destination) {
            popUpTo(navController.graph.findStartDestination().id) {
                inclusive = true
            }
            launchSingleTop = true
        }
    }

    Scaffold(
        bottomBar = {
            AnimatedVisibility(
                visible = showBottomBar,
                enter = slideInVertically(initialOffsetY = { it }) + fadeIn(),
                exit = slideOutVertically(targetOffsetY = { it }) + fadeOut(),
            ) {
                ExecutiveBottomDock(
                    items = mainDestinations,
                    currentRoute = currentRoute,
                    onNavigate = { destination ->
                        navController.navigate(destination.route) {
                            popUpTo(navController.graph.findStartDestination().id) {
                                saveState = true
                            }
                            launchSingleTop = true
                            restoreState = true
                        }
                    },
                )
            }
        },
    ) { innerPadding ->
        NavHost(
            navController = navController,
            startDestination = when {
                isLoggedIn -> AppDestination.Dashboard.route
                !hasSeenWelcome -> AppDestination.Welcome.route
                else -> AppDestination.Login.route
            },
            modifier = Modifier.padding(innerPadding),
        ) {
            composable(AppDestination.Welcome.route) {
                WelcomeScreen(
                    onContinue = {
                        onCompleteWelcome()
                        navController.navigate(AppDestination.Login.route) {
                            popUpTo(AppDestination.Welcome.route) {
                                inclusive = true
                            }
                            launchSingleTop = true
                        }
                    },
                )
            }

            composable(AppDestination.Login.route) {
                val viewModel: LoginViewModel = hiltViewModel()
                LoginScreen(viewModel = viewModel)
            }

            composable(AppDestination.Dashboard.route) {
                val viewModel: DashboardViewModel = hiltViewModel()
                DashboardScreen(
                    viewModel = viewModel,
                    onOpenRecap = { navController.navigate(AppDestination.Rekap.route) },
                    onOpenTargets = { navController.navigate(AppDestination.Targets.route) },
                    onOpenHpp = { navController.navigate(AppDestination.Hpp.route) },
                    onOpenAlerts = { navController.navigate(AppDestination.Alerts.route) },
                    onOpenDecisions = { navController.navigate(AppDestination.Decisions.route) },
                )
            }

            composable(AppDestination.Rekap.route) {
                val viewModel: RekapViewModel = hiltViewModel()
                RekapScreen(
                    viewModel = viewModel,
                    onBack = null,
                )
            }

            composable(AppDestination.Targets.route) {
                val viewModel: TargetsViewModel = hiltViewModel()
                TargetsScreen(
                    viewModel = viewModel,
                    onBack = { navController.popBackStack() },
                )
            }

            composable(AppDestination.Hpp.route) {
                val viewModel: HppViewModel = hiltViewModel()
                HppScreen(
                    viewModel = viewModel,
                    onBack = null,
                )
            }

            composable(AppDestination.Alerts.route) {
                val viewModel: AlertsViewModel = hiltViewModel()
                AlertsScreen(
                    viewModel = viewModel,
                    onBack = null,
                    onOpenDecisions = {
                        navController.navigate(AppDestination.Decisions.route) {
                            launchSingleTop = true
                        }
                    },
                )
            }

            composable(AppDestination.Decisions.route) {
                val viewModel: DecisionsViewModel = hiltViewModel()
                DecisionsScreen(
                    viewModel = viewModel,
                    onBack = null,
                )
            }
        }
    }
}

@Composable
private fun ExecutiveBottomDock(
    items: List<TopLevelDestination>,
    currentRoute: String?,
    onNavigate: (AppDestination) -> Unit,
) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 10.dp)
            .navigationBarsPadding(),
        shape = RoundedCornerShape(30.dp),
        color = Clay900,
        contentColor = Color.White,
        tonalElevation = 0.dp,
        shadowElevation = 20.dp,
        border = BorderStroke(
            width = 1.dp,
            brush = Brush.linearGradient(
                colors = listOf(Color.White.copy(alpha = 0.14f), Color.Transparent),
            ),
        ),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(8.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            items.forEach { item ->
                val selected = currentRoute == item.destination.route
                ExecutiveBottomDockItem(
                    item = item,
                    selected = selected,
                    onClick = { onNavigate(item.destination) },
                    modifier = Modifier.weight(1f),
                )
            }
        }
    }
}

@Composable
private fun ExecutiveBottomDockItem(
    item: TopLevelDestination,
    selected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val backgroundColor by animateColorAsState(
        targetValue = if (selected) Color.White.copy(alpha = 0.14f) else Color.Transparent,
        animationSpec = spring(stiffness = Spring.StiffnessMediumLow),
        label = "dock-item-bg",
    )
    val contentColor by animateColorAsState(
        targetValue = if (selected) Color.White else Color.White.copy(alpha = 0.72f),
        animationSpec = spring(stiffness = Spring.StiffnessMediumLow),
        label = "dock-item-content",
    )

    Box(
        modifier = modifier
            .clip(RoundedCornerShape(24.dp))
            .background(backgroundColor)
            .clickable(onClick = onClick)
            .padding(horizontal = 10.dp, vertical = 12.dp),
        contentAlignment = Alignment.Center,
    ) {
        Row(
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Surface(
                color = if (selected) Color.White.copy(alpha = 0.16f) else Clay300.copy(alpha = 0.18f),
                contentColor = contentColor,
                shape = RoundedCornerShape(16.dp),
            ) {
                Box(
                    modifier = Modifier.padding(8.dp),
                    contentAlignment = Alignment.Center,
                ) {
                    Icon(
                        imageVector = item.icon,
                        contentDescription = item.label,
                        modifier = Modifier.size(18.dp),
                    )
                }
            }
            AnimatedVisibility(
                visible = selected,
                enter = fadeIn() + androidx.compose.animation.expandHorizontally(
                    animationSpec = spring(stiffness = Spring.StiffnessLow),
                ),
                exit = fadeOut(),
            ) {
                Text(
                    text = item.label,
                    style = MaterialTheme.typography.labelLarge,
                    color = contentColor,
                    maxLines = 1,
                )
            }
        }
    }
}

private data class TopLevelDestination(
    val destination: AppDestination,
    val label: String,
    val icon: androidx.compose.ui.graphics.vector.ImageVector,
)
