import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../presentation/bloc/auth/auth_bloc.dart';
import '../../presentation/pages/auth/login_page.dart';
import '../../presentation/pages/auth/register_page.dart';
import '../../presentation/pages/auth/forgot_password_page.dart';
import '../../presentation/pages/home/home_page.dart';
import '../../presentation/pages/chat/chat_page.dart';
import '../../presentation/pages/channels/channels_page.dart';
import '../../presentation/pages/settings/settings_page.dart';
import '../../presentation/pages/splash/splash_page.dart';

class AppRouter {
  static final GoRouter router = GoRouter(
    initialLocation: '/splash',
    redirect: (BuildContext context, GoRouterState state) {
      final authState = context.read<AuthBloc>().state;
      final isAuthenticated = authState is AuthAuthenticated;
      final isLoading = authState is AuthLoading || authState is AuthInitial;

      // If still loading, go to splash
      if (isLoading && state.subloc != '/splash') {
        return '/splash';
      }

      // If not authenticated and trying to access protected routes
      if (!isAuthenticated && !_isPublicRoute(state.subloc)) {
        return '/login';
      }

      // If authenticated and trying to access auth routes
      if (isAuthenticated && _isAuthRoute(state.subloc)) {
        return '/home';
      }

      return null; // No redirect needed
    },
    routes: [
      // Splash Route
      GoRoute(
        path: '/splash',
        name: 'splash',
        builder: (context, state) => const SplashPage(),
      ),

      // Authentication Routes
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (context, state) => const LoginPage(),
      ),
      GoRoute(
        path: '/register',
        name: 'register',
        builder: (context, state) => const RegisterPage(),
      ),
      GoRoute(
        path: '/forgot-password',
        name: 'forgot-password',
        builder: (context, state) => const ForgotPasswordPage(),
      ),

      // Main App Routes
      GoRoute(
        path: '/home',
        name: 'home',
        builder: (context, state) => const HomePage(),
        routes: [
          GoRoute(
            path: 'chat/:channelId',
            name: 'chat',
            builder: (context, state) {
              final channelId = state.pathParameters['channelId']!;
              return ChatPage(channelId: channelId);
            },
          ),
          GoRoute(
            path: 'channels',
            name: 'channels',
            builder: (context, state) => const ChannelsPage(),
          ),
          GoRoute(
            path: 'settings',
            name: 'settings',
            builder: (context, state) => const SettingsPage(),
          ),
        ],
      ),
    ],
    errorBuilder: (context, state) => Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.error_outline,
              size: 64,
              color: Colors.red,
            ),
            const SizedBox(height: 16),
            Text(
              'Page not found',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 8),
            Text(
              state.error.toString(),
              style: Theme.of(context).textTheme.bodyMedium,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => context.go('/home'),
              child: const Text('Go Home'),
            ),
          ],
        ),
      ),
    ),
  );

  static bool _isPublicRoute(String route) {
    const publicRoutes = [
      '/splash',
      '/login',
      '/register',
      '/forgot-password',
    ];
    return publicRoutes.contains(route);
  }

  static bool _isAuthRoute(String route) {
    const authRoutes = [
      '/login',
      '/register',
      '/forgot-password',
    ];
    return authRoutes.contains(route);
  }
}