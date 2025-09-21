import 'package:dartz/dartz.dart';
import '../../core/errors/failures.dart';
import '../entities/auth_result.dart';
import '../entities/user.dart';

abstract class AuthRepository {
  /// Login with email and password
  Future<Either<Failure, AuthResult>> login({
    required String email,
    required String password,
    String? twoFactorCode,
  });

  /// Register a new account
  Future<Either<Failure, AuthResult>> register({
    required String name,
    required String email,
    required String password,
    required String organizationName,
  });

  /// Logout and clear tokens
  Future<Either<Failure, void>> logout();

  /// Refresh access token
  Future<Either<Failure, AuthResult>> refreshToken();

  /// Get current authenticated user
  Future<Either<Failure, User>> getCurrentUser();

  /// Check if user is authenticated
  Future<bool> isAuthenticated();

  /// Forgot password
  Future<Either<Failure, void>> forgotPassword({
    required String email,
  });

  /// Reset password
  Future<Either<Failure, void>> resetPassword({
    required String token,
    required String email,
    required String password,
    required String passwordConfirmation,
  });

  /// Change password
  Future<Either<Failure, void>> changePassword({
    required String currentPassword,
    required String newPassword,
    required String passwordConfirmation,
  });

  /// Update user profile
  Future<Either<Failure, User>> updateProfile({
    String? name,
    String? email,
    String? phone,
    String? bio,
    String? statusMessage,
    String? timezone,
  });

  /// Update user avatar
  Future<Either<Failure, User>> updateAvatar({
    required String imagePath,
  });

  /// Update user presence status
  Future<Either<Failure, void>> updatePresenceStatus({
    required String status,
  });

  /// Enable two-factor authentication
  Future<Either<Failure, Map<String, dynamic>>> enableTwoFactor();

  /// Confirm two-factor authentication setup
  Future<Either<Failure, List<String>>> confirmTwoFactor({
    required String code,
  });

  /// Disable two-factor authentication
  Future<Either<Failure, void>> disableTwoFactor({
    required String password,
  });

  /// Verify two-factor authentication code
  Future<Either<Failure, void>> verifyTwoFactor({
    required String code,
  });

  /// Resend email verification
  Future<Either<Failure, void>> resendEmailVerification();

  /// Verify email with token
  Future<Either<Failure, void>> verifyEmail({
    required String token,
  });

  /// Check biometric availability
  Future<Either<Failure, bool>> isBiometricAvailable();

  /// Enable biometric authentication
  Future<Either<Failure, void>> enableBiometric();

  /// Disable biometric authentication
  Future<Either<Failure, void>> disableBiometric();

  /// Authenticate with biometric
  Future<Either<Failure, bool>> authenticateWithBiometric();

  /// Delete account
  Future<Either<Failure, void>> deleteAccount({
    required String password,
  });
}