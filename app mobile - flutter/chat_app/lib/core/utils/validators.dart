import '../constants/app_constants.dart';

class Validators {
  // Email validation
  static String? validateEmail(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Email is required';
    }

    final emailRegExp = RegExp(AppConstants.emailRegex);
    if (!emailRegExp.hasMatch(value.trim())) {
      return 'Please enter a valid email address';
    }

    return null;
  }

  // Password validation
  static String? validatePassword(String? value) {
    if (value == null || value.isEmpty) {
      return 'Password is required';
    }

    if (value.length < 8) {
      return 'Password must be at least 8 characters long';
    }

    if (!value.contains(RegExp(r'[A-Z]'))) {
      return 'Password must contain at least one uppercase letter';
    }

    if (!value.contains(RegExp(r'[a-z]'))) {
      return 'Password must contain at least one lowercase letter';
    }

    if (!value.contains(RegExp(r'[0-9]'))) {
      return 'Password must contain at least one number';
    }

    return null;
  }

  // Confirm password validation
  static String? validateConfirmPassword(String? value, String? password) {
    if (value == null || value.isEmpty) {
      return 'Please confirm your password';
    }

    if (value != password) {
      return 'Passwords do not match';
    }

    return null;
  }

  // Name validation
  static String? validateName(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Name is required';
    }

    if (value.trim().length < 2) {
      return 'Name must be at least 2 characters long';
    }

    if (value.trim().length > 50) {
      return 'Name must be less than 50 characters';
    }

    if (!RegExp(r'^[a-zA-Z\s\-\.\']+$').hasMatch(value.trim())) {
      return 'Name can only contain letters, spaces, hyphens, dots, and apostrophes';
    }

    return null;
  }

  // Organization name validation
  static String? validateOrganizationName(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Organization name is required';
    }

    if (value.trim().length < 2) {
      return 'Organization name must be at least 2 characters long';
    }

    if (value.trim().length > 100) {
      return 'Organization name must be less than 100 characters';
    }

    return null;
  }

  // Channel name validation
  static String? validateChannelName(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Channel name is required';
    }

    if (value.trim().length < 2) {
      return 'Channel name must be at least 2 characters long';
    }

    if (value.trim().length > AppConstants.maxChannelNameLength) {
      return 'Channel name must be less than ${AppConstants.maxChannelNameLength} characters';
    }

    if (!RegExp(r'^[a-z0-9\-_]+$').hasMatch(value.trim())) {
      return 'Channel name can only contain lowercase letters, numbers, hyphens, and underscores';
    }

    if (value.trim().startsWith('-') || value.trim().endsWith('-')) {
      return 'Channel name cannot start or end with a hyphen';
    }

    return null;
  }

  // Channel description validation
  static String? validateChannelDescription(String? value) {
    if (value != null && value.trim().length > AppConstants.maxChannelDescriptionLength) {
      return 'Description must be less than ${AppConstants.maxChannelDescriptionLength} characters';
    }

    return null;
  }

  // Message validation
  static String? validateMessage(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Message cannot be empty';
    }

    if (value.trim().length > AppConstants.maxMessageLength) {
      return 'Message must be less than ${AppConstants.maxMessageLength} characters';
    }

    return null;
  }

  // Status message validation
  static String? validateStatusMessage(String? value) {
    if (value != null && value.trim().length > AppConstants.maxStatusMessageLength) {
      return 'Status message must be less than ${AppConstants.maxStatusMessageLength} characters';
    }

    return null;
  }

  // Phone number validation
  static String? validatePhoneNumber(String? value) {
    if (value == null || value.trim().isEmpty) {
      return null; // Phone number is optional
    }

    final phoneRegExp = RegExp(AppConstants.phoneRegex);
    if (!phoneRegExp.hasMatch(value.trim())) {
      return 'Please enter a valid phone number';
    }

    return null;
  }

  // Generic required field validation
  static String? validateRequired(String? value, String fieldName) {
    if (value == null || value.trim().isEmpty) {
      return '$fieldName is required';
    }
    return null;
  }

  // Generic length validation
  static String? validateLength(
    String? value,
    String fieldName, {
    int? minLength,
    int? maxLength,
  }) {
    if (value == null) return null;

    if (minLength != null && value.length < minLength) {
      return '$fieldName must be at least $minLength characters long';
    }

    if (maxLength != null && value.length > maxLength) {
      return '$fieldName must be less than $maxLength characters';
    }

    return null;
  }

  // URL validation
  static String? validateUrl(String? value) {
    if (value == null || value.trim().isEmpty) {
      return null; // URL is optional
    }

    final urlRegExp = RegExp(AppConstants.urlRegex);
    if (!urlRegExp.hasMatch(value.trim())) {
      return 'Please enter a valid URL';
    }

    return null;
  }

  // File size validation
  static String? validateFileSize(int fileSizeInBytes, {int? maxSize}) {
    final maxSizeInBytes = maxSize ?? AppConstants.maxFileSize;

    if (fileSizeInBytes > maxSizeInBytes) {
      final maxSizeInMB = (maxSizeInBytes / (1024 * 1024)).toStringAsFixed(1);
      return 'File size cannot exceed ${maxSizeInMB}MB';
    }

    return null;
  }

  // File type validation
  static String? validateFileType(String fileName, List<String> allowedTypes) {
    final extension = fileName.split('.').last.toLowerCase();

    if (!allowedTypes.contains(extension)) {
      return 'File type .$extension is not supported. Allowed types: ${allowedTypes.join(', ')}';
    }

    return null;
  }

  // Image file validation
  static String? validateImageFile(String fileName, int fileSizeInBytes) {
    // Check file type
    final typeValidation = validateFileType(fileName, AppConstants.allowedImageTypes);
    if (typeValidation != null) {
      return typeValidation;
    }

    // Check file size
    final sizeValidation = validateFileSize(fileSizeInBytes, maxSize: AppConstants.maxImageSize);
    if (sizeValidation != null) {
      return sizeValidation;
    }

    return null;
  }

  // Regular file validation
  static String? validateRegularFile(String fileName, int fileSizeInBytes) {
    // Check file type
    final allowedTypes = [
      ...AppConstants.allowedImageTypes,
      ...AppConstants.allowedFileTypes,
    ];
    final typeValidation = validateFileType(fileName, allowedTypes);
    if (typeValidation != null) {
      return typeValidation;
    }

    // Check file size
    final sizeValidation = validateFileSize(fileSizeInBytes);
    if (sizeValidation != null) {
      return sizeValidation;
    }

    return null;
  }

  // Combine multiple validators
  static String? validateMultiple(String? value, List<String? Function(String?)> validators) {
    for (final validator in validators) {
      final result = validator(value);
      if (result != null) {
        return result;
      }
    }
    return null;
  }

  // Check if value contains profanity (basic implementation)
  static String? validateProfanity(String? value) {
    if (value == null || value.trim().isEmpty) {
      return null;
    }

    // Basic profanity filter - in a real app, you'd use a more sophisticated service
    final profanityWords = ['spam', 'scam', 'fake', 'bot'];
    final lowercaseValue = value.toLowerCase();

    for (final word in profanityWords) {
      if (lowercaseValue.contains(word)) {
        return 'Message contains inappropriate content';
      }
    }

    return null;
  }

  // Validate mention format
  static bool isValidMention(String text) {
    final mentionRegExp = RegExp(AppConstants.mentionRegex);
    return mentionRegExp.hasMatch(text);
  }

  // Validate hashtag format
  static bool isValidHashtag(String text) {
    final hashtagRegExp = RegExp(AppConstants.hashtagRegex);
    return hashtagRegExp.hasMatch(text);
  }

  // Extract mentions from text
  static List<String> extractMentions(String text) {
    final mentionRegExp = RegExp(AppConstants.mentionRegex, multiLine: true);
    return mentionRegExp
        .allMatches(text)
        .map((match) => match.group(1) ?? '')
        .where((mention) => mention.isNotEmpty)
        .toList();
  }

  // Extract hashtags from text
  static List<String> extractHashtags(String text) {
    final hashtagRegExp = RegExp(AppConstants.hashtagRegex, multiLine: true);
    return hashtagRegExp
        .allMatches(text)
        .map((match) => match.group(1) ?? '')
        .where((hashtag) => hashtag.isNotEmpty)
        .toList();
  }

  // Extract URLs from text
  static List<String> extractUrls(String text) {
    final urlRegExp = RegExp(AppConstants.urlRegex, multiLine: true);
    return urlRegExp
        .allMatches(text)
        .map((match) => match.group(0) ?? '')
        .where((url) => url.isNotEmpty)
        .toList();
  }
}