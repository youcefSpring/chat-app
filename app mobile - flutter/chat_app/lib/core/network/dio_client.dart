import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../constants/api_constants.dart';
import '../storage/token_storage.dart';
import '../errors/exceptions.dart';

class DioClient {
  late final Dio _dio;
  final TokenStorage _tokenStorage;

  DioClient(this._tokenStorage) {
    _dio = Dio();
    _setupInterceptors();
  }

  Dio get dio => _dio;

  void _setupInterceptors() {
    _dio.options = BaseOptions(
      baseUrl: ApiConstants.apiUrl,
      connectTimeout: Duration(milliseconds: ApiConstants.connectTimeout),
      receiveTimeout: Duration(milliseconds: ApiConstants.receiveTimeout),
      sendTimeout: Duration(milliseconds: ApiConstants.sendTimeout),
      headers: {
        ApiConstants.contentTypeHeader: ApiConstants.applicationJson,
        ApiConstants.acceptHeader: ApiConstants.applicationJson,
      },
    );

    // Add auth interceptor
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.getAccessToken();
          if (token != null) {
            options.headers[ApiConstants.authorizationHeader] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) async {
          if (error.response?.statusCode == 401) {
            // Token expired, try to refresh
            final refreshed = await _refreshToken();
            if (refreshed) {
              // Retry the original request
              final options = error.requestOptions;
              final token = await _tokenStorage.getAccessToken();
              if (token != null) {
                options.headers[ApiConstants.authorizationHeader] = 'Bearer $token';
              }

              try {
                final response = await _dio.fetch(options);
                handler.resolve(response);
                return;
              } catch (e) {
                // If retry fails, continue with original error
              }
            }
          }
          handler.next(error);
        },
      ),
    );

    // Add logging interceptor in debug mode
    if (kDebugMode) {
      _dio.interceptors.add(
        LogInterceptor(
          requestBody: true,
          responseBody: true,
          requestHeader: true,
          responseHeader: false,
          error: true,
          logPrint: (obj) => debugPrint(obj.toString()),
        ),
      );
    }

    // Add error handling interceptor
    _dio.interceptors.add(
      InterceptorsWrapper(
        onError: (error, handler) {
          final exception = _handleDioError(error);
          handler.reject(DioException.requestCancelled(
            requestOptions: error.requestOptions,
            reason: exception.toString(),
          ));
        },
      ),
    );
  }

  Future<bool> _refreshToken() async {
    try {
      final refreshToken = await _tokenStorage.getRefreshToken();
      if (refreshToken == null) {
        await _tokenStorage.clearTokens();
        return false;
      }

      final refreshDio = Dio();
      refreshDio.options.baseUrl = ApiConstants.apiUrl;

      final response = await refreshDio.post(
        ApiConstants.refreshToken,
        data: {'refresh_token': refreshToken},
      );

      if (response.statusCode == 200) {
        final data = response.data;
        if (data['access_token'] != null) {
          await _tokenStorage.saveAccessToken(data['access_token']);
          if (data['refresh_token'] != null) {
            await _tokenStorage.saveRefreshToken(data['refresh_token']);
          }
          return true;
        }
      }
    } catch (e) {
      debugPrint('Token refresh failed: $e');
    }

    await _tokenStorage.clearTokens();
    return false;
  }

  Exception _handleDioError(DioException error) {
    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return const NetworkException(
          message: 'Connection timeout. Please check your internet connection.',
        );

      case DioExceptionType.badResponse:
        return _handleStatusCode(error);

      case DioExceptionType.cancel:
        return const NetworkException(message: 'Request was cancelled.');

      case DioExceptionType.connectionError:
        return const NetworkException(
          message: 'Connection failed. Please check your internet connection.',
        );

      case DioExceptionType.badCertificate:
        return const NetworkException(
          message: 'Certificate verification failed.',
        );

      case DioExceptionType.unknown:
      default:
        return NetworkException(
          message: 'Network error: ${error.message ?? 'Unknown error'}',
        );
    }
  }

  Exception _handleStatusCode(DioException error) {
    final statusCode = error.response?.statusCode;
    final message = _getErrorMessage(error.response?.data);

    switch (statusCode) {
      case 400:
        return ValidationException(
          message: message ?? 'Bad request. Please check your input.',
          errors: _getValidationErrors(error.response?.data),
        );

      case 401:
        return AuthException(
          message: message ?? 'Authentication failed. Please login again.',
          statusCode: 401,
        );

      case 403:
        return AuthException(
          message: message ?? 'Access forbidden. You don\'t have permission.',
          statusCode: 403,
        );

      case 404:
        return ServerException(
          message: message ?? 'Resource not found.',
          statusCode: 404,
        );

      case 409:
        return ServerException(
          message: message ?? 'Conflict occurred.',
          statusCode: 409,
        );

      case 422:
        return ValidationException(
          message: message ?? 'Validation failed.',
          errors: _getValidationErrors(error.response?.data),
        );

      case 429:
        return ServerException(
          message: message ?? 'Too many requests. Please try again later.',
          statusCode: 429,
        );

      case 500:
      case 502:
      case 503:
      case 504:
        return ServerException(
          message: message ?? 'Server error. Please try again later.',
          statusCode: statusCode,
        );

      default:
        return ServerException(
          message: message ?? 'An unexpected error occurred.',
          statusCode: statusCode,
        );
    }
  }

  String? _getErrorMessage(dynamic responseData) {
    if (responseData is Map<String, dynamic>) {
      return responseData['message'] as String? ??
          responseData['error'] as String? ??
          responseData['detail'] as String?;
    }
    return null;
  }

  Map<String, List<String>>? _getValidationErrors(dynamic responseData) {
    if (responseData is Map<String, dynamic>) {
      final errors = responseData['errors'];
      if (errors is Map<String, dynamic>) {
        return errors.map((key, value) {
          if (value is List) {
            return MapEntry(key, value.cast<String>());
          } else if (value is String) {
            return MapEntry(key, [value]);
          }
          return MapEntry(key, [value.toString()]);
        });
      }
    }
    return null;
  }

  // Utility methods for different HTTP methods
  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      return await _dio.get<T>(
        path,
        queryParameters: queryParameters,
        options: options,
      );
    } catch (e) {
      rethrow;
    }
  }

  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      return await _dio.post<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
    } catch (e) {
      rethrow;
    }
  }

  Future<Response<T>> put<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      return await _dio.put<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
    } catch (e) {
      rethrow;
    }
  }

  Future<Response<T>> patch<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      return await _dio.patch<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
    } catch (e) {
      rethrow;
    }
  }

  Future<Response<T>> delete<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      return await _dio.delete<T>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
    } catch (e) {
      rethrow;
    }
  }
}