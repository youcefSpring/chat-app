part of 'auth_bloc.dart';

abstract class AuthEvent extends Equatable {
  const AuthEvent();

  @override
  List<Object?> get props => [];
}

class AuthCheckRequested extends AuthEvent {}

class LoginRequested extends AuthEvent {
  final String email;
  final String password;
  final String? twoFactorCode;

  const LoginRequested({
    required this.email,
    required this.password,
    this.twoFactorCode,
  });

  @override
  List<Object?> get props => [email, password, twoFactorCode];
}

class RegisterRequested extends AuthEvent {
  final String name;
  final String email;
  final String password;
  final String passwordConfirmation;
  final String organizationName;

  const RegisterRequested({
    required this.name,
    required this.email,
    required this.password,
    required this.passwordConfirmation,
    required this.organizationName,
  });

  @override
  List<Object?> get props => [
        name,
        email,
        password,
        passwordConfirmation,
        organizationName,
      ];
}

class LogoutRequested extends AuthEvent {}

class ForgotPasswordRequested extends AuthEvent {
  final String email;

  const ForgotPasswordRequested({required this.email});

  @override
  List<Object?> get props => [email];
}

class ResetPasswordRequested extends AuthEvent {
  final String token;
  final String email;
  final String password;
  final String passwordConfirmation;

  const ResetPasswordRequested({
    required this.token,
    required this.email,
    required this.password,
    required this.passwordConfirmation,
  });

  @override
  List<Object?> get props => [token, email, password, passwordConfirmation];
}

class ChangePasswordRequested extends AuthEvent {
  final String currentPassword;
  final String newPassword;
  final String passwordConfirmation;

  const ChangePasswordRequested({
    required this.currentPassword,
    required this.newPassword,
    required this.passwordConfirmation,
  });

  @override
  List<Object?> get props => [currentPassword, newPassword, passwordConfirmation];
}

class UpdateProfileRequested extends AuthEvent {
  final String? name;
  final String? email;
  final String? phone;
  final String? bio;
  final String? statusMessage;
  final String? timezone;

  const UpdateProfileRequested({
    this.name,
    this.email,
    this.phone,
    this.bio,
    this.statusMessage,
    this.timezone,
  });

  @override
  List<Object?> get props => [name, email, phone, bio, statusMessage, timezone];
}

class UpdateAvatarRequested extends AuthEvent {
  final String imagePath;

  const UpdateAvatarRequested({required this.imagePath});

  @override
  List<Object?> get props => [imagePath];
}

class UpdatePresenceRequested extends AuthEvent {
  final String status;

  const UpdatePresenceRequested({required this.status});

  @override
  List<Object?> get props => [status];
}

class RefreshTokenRequested extends AuthEvent {}