import 'package:dartz/dartz.dart';
import 'package:dio/dio.dart';

import '../../core/errors/failures.dart';
import '../../core/errors/exceptions.dart';
import '../../core/network/api_client.dart';
import '../../core/storage/token_storage.dart';
import '../../domain/entities/auth_result.dart';
import '../../domain/entities/user.dart';
import '../../domain/repositories/auth_repository.dart';
import '../models/auth_result_model.dart';
import '../models/user_model.dart';

class AuthRepositoryImpl implements AuthRepository {
  final ApiClient apiClient;
  final TokenStorage tokenStorage;

  AuthRepositoryImpl({
    required this.apiClient,
    required this.tokenStorage,
  });

  @override
  Future<Either<Failure, AuthResult>> login({
    required String email,
    required String password,
    String? twoFactorCode,
  }) async {
    try {
      final requestData = {
        'email': email,
        'password': password,
        if (twoFactorCode != null) 'two_factor_code': twoFactorCode,
      };

      final response = await apiClient.login(requestData);

      if (response.response.statusCode == 200) {
        final authResultModel = AuthResultModel.fromJson(response.data);
        final authResult = authResultModel.toEntity();

        // Save tokens
        await tokenStorage.saveTokens(
          authResult.accessToken,
          authResult.refreshToken,
        );

        return Right(authResult);
      } else {
        return Left(ServerFailure(
          message: 'Login failed',
          code: response.response.statusCode,
        ));
      }
    } on DioException catch (e) {
      return Left(_handleDioException(e));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, AuthResult>> register({
    required String name,
    required String email,
    required String password,
    required String organizationName,
  }) async {
    try {
      final requestData = {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
        'organization_name': organizationName,
      };

      final response = await apiClient.register(requestData);

      if (response.response.statusCode == 201) {
        final authResultModel = AuthResultModel.fromJson(response.data);
        final authResult = authResultModel.toEntity();

        // Save tokens
        await tokenStorage.saveTokens(
          authResult.accessToken,
          authResult.refreshToken,
        );

        return Right(authResult);
      } else {
        return Left(ServerFailure(
          message: 'Registration failed',
          code: response.response.statusCode,
        ));
      }
    } on DioException catch (e) {
      return Left(_handleDioException(e));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, void>> logout() async {
    try {
      await apiClient.logout();
      await tokenStorage.clearTokens();
      return const Right(null);
    } on DioException catch (e) {
      // Clear tokens even if API call fails
      await tokenStorage.clearTokens();
      return Left(_handleDioException(e));
    } catch (e) {
      await tokenStorage.clearTokens();
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, AuthResult>> refreshToken() async {
    try {
      final refreshToken = await tokenStorage.getRefreshToken();
      if (refreshToken == null) {
        return const Left(AuthFailure(message: 'No refresh token available'));
      }

      final response = await apiClient.refreshToken({'refresh_token': refreshToken});

      if (response.response.statusCode == 200) {
        final authResultModel = AuthResultModel.fromJson(response.data);
        final authResult = authResultModel.toEntity();

        // Save new tokens
        await tokenStorage.saveTokens(
          authResult.accessToken,
          authResult.refreshToken,
        );

        return Right(authResult);
      } else {
        await tokenStorage.clearTokens();
        return Left(TokenExpiredFailure(message: 'Token refresh failed'));
      }
    } on DioException catch (e) {
      await tokenStorage.clearTokens();
      return Left(_handleDioException(e));
    } catch (e) {
      await tokenStorage.clearTokens();
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, User>> getCurrentUser() async {
    try {
      final response = await apiClient.getUserProfile();

      if (response.response.statusCode == 200) {
        final userModel = UserModel.fromJson(response.data['user']);
        return Right(userModel.toEntity());
      } else {
        return Left(ServerFailure(
          message: 'Failed to get user profile',
          code: response.response.statusCode,
        ));
      }
    } on DioException catch (e) {
      return Left(_handleDioException(e));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<bool> isAuthenticated() async {
    return await tokenStorage.hasValidTokens();
  }

  @override
  Future<Either<Failure, void>> forgotPassword({required String email}) async {
    try {
      final response = await apiClient.forgotPassword({'email': email});

      if (response.response.statusCode == 200) {
        return const Right(null);
      } else {
        return Left(ServerFailure(
          message: 'Failed to send password reset email',
          code: response.response.statusCode,
        ));
      }
    } on DioException catch (e) {
      return Left(_handleDioException(e));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, void>> resetPassword({
    required String token,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final requestData = {
        'token': token,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
      };

      final response = await apiClient.resetPassword(requestData);

      if (response.response.statusCode == 200) {
        return const Right(null);
      } else {
        return Left(ServerFailure(
          message: 'Failed to reset password',
          code: response.response.statusCode,
        ));
      }
    } on DioException catch (e) {
      return Left(_handleDioException(e));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, void>> changePassword({
    required String currentPassword,
    required String newPassword,
    required String passwordConfirmation,
  }) async {
    try {
      final requestData = {
        'current_password': currentPassword,
        'password': newPassword,
        'password_confirmation': passwordConfirmation,
      };

      final response = await apiClient.changePassword(requestData);

      if (response.response.statusCode == 200) {
        return const Right(null);
      } else {
        return Left(ServerFailure(
          message: 'Failed to change password',
          code: response.response.statusCode,
        ));
      }
    } on DioException catch (e) {
      return Left(_handleDioException(e));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, User>> updateProfile({
    String? name,
    String? email,
    String? phone,
    String? bio,
    String? statusMessage,
    String? timezone,
  }) async {
    try {
      final requestData = <String, dynamic>{};
      if (name != null) requestData['name'] = name;
      if (email != null) requestData['email'] = email;
      if (phone != null) requestData['phone'] = phone;
      if (bio != null) requestData['bio'] = bio;
      if (statusMessage != null) requestData['status_message'] = statusMessage;
      if (timezone != null) requestData['timezone'] = timezone;

      final response = await apiClient.updateProfile(requestData);

      if (response.response.statusCode == 200) {
        final userModel = UserModel.fromJson(response.data['user']);
        return Right(userModel.toEntity());
      } else {
        return Left(ServerFailure(
          message: 'Failed to update profile',
          code: response.response.statusCode,
        ));
      }
    } on DioException catch (e) {
      return Left(_handleDioException(e));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, User>> updateAvatar({required String imagePath}) async {
    try {
      // This would need multipart upload implementation
      // For now, return a basic implementation
      return Left(UnknownFailure(message: 'Avatar update not implemented'));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  @override
  Future<Either<Failure, void>> updatePresenceStatus({required String status}) async {
    try {
      final response = await apiClient.updatePresence({'status': status});

      if (response.response.statusCode == 200) {
        return const Right(null);
      } else {
        return Left(ServerFailure(
          message: 'Failed to update presence',
          code: response.response.statusCode,
        ));
      }
    } on DioException catch (e) {
      return Left(_handleDioException(e));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  // Stub implementations for methods not yet implemented
  @override
  Future<Either<Failure, Map<String, dynamic>>> enableTwoFactor() async {
    return Left(UnknownFailure(message: 'Two-factor setup not implemented'));
  }

  @override
  Future<Either<Failure, List<String>>> confirmTwoFactor({required String code}) async {
    return Left(UnknownFailure(message: 'Two-factor confirmation not implemented'));
  }

  @override
  Future<Either<Failure, void>> disableTwoFactor({required String password}) async {
    return Left(UnknownFailure(message: 'Two-factor disable not implemented'));
  }

  @override
  Future<Either<Failure, void>> verifyTwoFactor({required String code}) async {
    return Left(UnknownFailure(message: 'Two-factor verification not implemented'));
  }

  @override
  Future<Either<Failure, void>> resendEmailVerification() async {
    return Left(UnknownFailure(message: 'Email verification not implemented'));
  }

  @override
  Future<Either<Failure, void>> verifyEmail({required String token}) async {
    return Left(UnknownFailure(message: 'Email verification not implemented'));
  }

  @override
  Future<Either<Failure, bool>> isBiometricAvailable() async {
    return Left(UnknownFailure(message: 'Biometric check not implemented'));
  }

  @override
  Future<Either<Failure, void>> enableBiometric() async {
    return Left(UnknownFailure(message: 'Biometric enable not implemented'));
  }

  @override
  Future<Either<Failure, void>> disableBiometric() async {
    return Left(UnknownFailure(message: 'Biometric disable not implemented'));
  }

  @override
  Future<Either<Failure, bool>> authenticateWithBiometric() async {
    return Left(UnknownFailure(message: 'Biometric auth not implemented'));
  }

  @override
  Future<Either<Failure, void>> deleteAccount({required String password}) async {
    return Left(UnknownFailure(message: 'Account deletion not implemented'));
  }

  Failure _handleDioException(DioException e) {
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return const TimeoutFailure(message: 'Request timeout');
      case DioExceptionType.badResponse:
        final statusCode = e.response?.statusCode;
        final message = e.response?.data?['message'] ?? 'Server error';

        switch (statusCode) {
          case 401:
            return AuthFailure(message: message, code: statusCode);
          case 422:
            return ValidationFailure(message: message);
          default:
            return ServerFailure(message: message, code: statusCode);
        }
      case DioExceptionType.cancel:
        return const NetworkFailure(message: 'Request cancelled');
      case DioExceptionType.connectionError:
        return const ConnectionFailure(message: 'No internet connection');
      default:
        return NetworkFailure(message: e.message ?? 'Network error');
    }
  }
}