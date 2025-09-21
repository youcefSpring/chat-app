part of 'auth_bloc.dart';

abstract class AuthState extends Equatable {
  const AuthState();

  @override
  List<Object?> get props => [];
}

class AuthInitial extends AuthState {}

class AuthLoading extends AuthState {}

class AuthAuthenticated extends AuthState {
  final User user;

  const AuthAuthenticated({required this.user});

  @override
  List<Object?> get props => [user];
}

class AuthUnauthenticated extends AuthState {}

class AuthError extends AuthState {
  final String message;
  final Failure failure;

  const AuthError({
    required this.message,
    required this.failure,
  });

  @override
  List<Object?> get props => [message, failure];
}

class ForgotPasswordSuccess extends AuthState {}

class ResetPasswordSuccess extends AuthState {}

class PasswordChangeSuccess extends AuthState {
  final User user;

  const PasswordChangeSuccess({required this.user});

  @override
  List<Object?> get props => [user];
}

class ProfileUpdateSuccess extends AuthState {
  final User user;

  const ProfileUpdateSuccess({required this.user});

  @override
  List<Object?> get props => [user];
}