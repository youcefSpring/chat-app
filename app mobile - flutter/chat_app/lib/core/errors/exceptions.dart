class ServerException implements Exception {
  final String message;
  final int? statusCode;

  const ServerException({
    required this.message,
    this.statusCode,
  });

  @override
  String toString() => 'ServerException: $message (Status: $statusCode)';
}

class NetworkException implements Exception {
  final String message;

  const NetworkException({required this.message});

  @override
  String toString() => 'NetworkException: $message';
}

class CacheException implements Exception {
  final String message;

  const CacheException({required this.message});

  @override
  String toString() => 'CacheException: $message';
}

class AuthException implements Exception {
  final String message;
  final int? statusCode;

  const AuthException({
    required this.message,
    this.statusCode,
  });

  @override
  String toString() => 'AuthException: $message (Status: $statusCode)';
}

class ValidationException implements Exception {
  final String message;
  final Map<String, List<String>>? errors;

  const ValidationException({
    required this.message,
    this.errors,
  });

  @override
  String toString() => 'ValidationException: $message';
}

class StorageException implements Exception {
  final String message;

  const StorageException({required this.message});

  @override
  String toString() => 'StorageException: $message';
}

class FileUploadException implements Exception {
  final String message;
  final int? statusCode;

  const FileUploadException({
    required this.message,
    this.statusCode,
  });

  @override
  String toString() => 'FileUploadException: $message (Status: $statusCode)';
}

class WebSocketException implements Exception {
  final String message;

  const WebSocketException({required this.message});

  @override
  String toString() => 'WebSocketException: $message';
}

class PermissionException implements Exception {
  final String message;

  const PermissionException({required this.message});

  @override
  String toString() => 'PermissionException: $message';
}

class BiometricException implements Exception {
  final String message;

  const BiometricException({required this.message});

  @override
  String toString() => 'BiometricException: $message';
}

class CallException implements Exception {
  final String message;

  const CallException({required this.message});

  @override
  String toString() => 'CallException: $message';
}

class ParsingException implements Exception {
  final String message;

  const ParsingException({required this.message});

  @override
  String toString() => 'ParsingException: $message';
}