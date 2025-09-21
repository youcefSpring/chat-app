import 'package:equatable/equatable.dart';

abstract class Failure extends Equatable {
  final String message;
  final int? code;

  const Failure({required this.message, this.code});

  @override
  List<Object?> get props => [message, code];
}

// Network Failures
class NetworkFailure extends Failure {
  const NetworkFailure({required super.message, super.code});
}

class ServerFailure extends Failure {
  const ServerFailure({required super.message, super.code});
}

class ConnectionFailure extends Failure {
  const ConnectionFailure({required super.message})
      : super(code: null);
}

class TimeoutFailure extends Failure {
  const TimeoutFailure({required super.message})
      : super(code: 408);
}

// Authentication Failures
class AuthFailure extends Failure {
  const AuthFailure({required super.message, super.code});
}

class UnauthorizedFailure extends AuthFailure {
  const UnauthorizedFailure({required super.message})
      : super(code: 401);
}

class ForbiddenFailure extends AuthFailure {
  const ForbiddenFailure({required super.message})
      : super(code: 403);
}

class TokenExpiredFailure extends AuthFailure {
  const TokenExpiredFailure({required super.message})
      : super(code: 401);
}

// Validation Failures
class ValidationFailure extends Failure {
  final Map<String, List<String>>? errors;

  const ValidationFailure({
    required super.message,
    this.errors,
  }) : super(code: 422);

  @override
  List<Object?> get props => [message, code, errors];
}

// Data Failures
class CacheFailure extends Failure {
  const CacheFailure({required super.message})
      : super(code: null);
}

class StorageFailure extends Failure {
  const StorageFailure({required super.message})
      : super(code: null);
}

class ParsingFailure extends Failure {
  const ParsingFailure({required super.message})
      : super(code: null);
}

// Resource Failures
class NotFoundFailure extends Failure {
  const NotFoundFailure({required super.message})
      : super(code: 404);
}

class ConflictFailure extends Failure {
  const ConflictFailure({required super.message})
      : super(code: 409);
}

// Permission Failures
class PermissionFailure extends Failure {
  const PermissionFailure({required super.message})
      : super(code: null);
}

class BiometricFailure extends Failure {
  const BiometricFailure({required super.message})
      : super(code: null);
}

// File Failures
class FileUploadFailure extends Failure {
  const FileUploadFailure({required super.message, super.code});
}

class FileSizeExceededFailure extends FileUploadFailure {
  const FileSizeExceededFailure({required super.message})
      : super(code: null);
}

class UnsupportedFileTypeFailure extends FileUploadFailure {
  const UnsupportedFileTypeFailure({required super.message})
      : super(code: null);
}

// WebSocket Failures
class WebSocketFailure extends Failure {
  const WebSocketFailure({required super.message, super.code});
}

class WebSocketConnectionFailure extends WebSocketFailure {
  const WebSocketConnectionFailure({required super.message})
      : super(code: null);
}

class WebSocketDisconnectedFailure extends WebSocketFailure {
  const WebSocketDisconnectedFailure({required super.message})
      : super(code: null);
}

// Call Failures
class CallFailure extends Failure {
  const CallFailure({required super.message, super.code});
}

class CallPermissionFailure extends CallFailure {
  const CallPermissionFailure({required super.message})
      : super(code: null);
}

class CallConnectionFailure extends CallFailure {
  const CallConnectionFailure({required super.message})
      : super(code: null);
}

// Unknown/Generic Failures
class UnknownFailure extends Failure {
  const UnknownFailure({required super.message, super.code});
}

// Failure Helper Class
class FailureHelper {
  static Failure mapExceptionToFailure(dynamic exception) {
    if (exception is NetworkFailure ||
        exception is ServerFailure ||
        exception is AuthFailure ||
        exception is ValidationFailure ||
        exception is CacheFailure ||
        exception is StorageFailure ||
        exception is ParsingFailure ||
        exception is NotFoundFailure ||
        exception is ConflictFailure ||
        exception is PermissionFailure ||
        exception is FileUploadFailure ||
        exception is WebSocketFailure ||
        exception is CallFailure) {
      return exception;
    }

    return UnknownFailure(
      message: exception.toString(),
      code: null,
    );
  }

  static String getDisplayMessage(Failure failure) {
    switch (failure.runtimeType) {
      case ConnectionFailure:
      case NetworkFailure:
        return 'Network connection failed. Please check your internet connection.';
      case TimeoutFailure:
        return 'Request timed out. Please try again.';
      case ServerFailure:
        return 'Server error occurred. Please try again later.';
      case UnauthorizedFailure:
      case TokenExpiredFailure:
        return 'Your session has expired. Please login again.';
      case ForbiddenFailure:
        return 'You don\'t have permission to perform this action.';
      case ValidationFailure:
        return 'Please check your input and try again.';
      case NotFoundFailure:
        return 'The requested resource was not found.';
      case ConflictFailure:
        return 'A conflict occurred. Please try again.';
      case CacheFailure:
      case StorageFailure:
        return 'Local storage error. Please restart the app.';
      case ParsingFailure:
        return 'Data parsing error. Please try again.';
      case PermissionFailure:
        return 'Permission denied. Please grant required permissions.';
      case BiometricFailure:
        return 'Biometric authentication failed.';
      case FileSizeExceededFailure:
        return 'File size exceeds the maximum allowed limit.';
      case UnsupportedFileTypeFailure:
        return 'File type is not supported.';
      case WebSocketConnectionFailure:
        return 'Real-time connection failed. Some features may not work.';
      case CallPermissionFailure:
        return 'Microphone/Camera permission required for calls.';
      case CallConnectionFailure:
        return 'Call connection failed. Please try again.';
      default:
        return failure.message.isNotEmpty ? failure.message : 'An unknown error occurred.';
    }
  }
}