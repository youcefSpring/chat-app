class AppConstants {
  // App Information
  static const String appName = 'Chat App';
  static const String appVersion = '1.0.0';
  static const String appDescription = 'Modern team communication platform';

  // Storage Keys
  static const String tokenKey = 'auth_token';
  static const String refreshTokenKey = 'refresh_token';
  static const String userKey = 'user_data';
  static const String organizationKey = 'organization_data';
  static const String themeKey = 'theme_mode';
  static const String languageKey = 'language_code';
  static const String notificationSettingsKey = 'notification_settings';
  static const String chatSettingsKey = 'chat_settings';

  // Hive Box Names
  static const String userBox = 'user_box';
  static const String chatBox = 'chat_box';
  static const String settingsBox = 'settings_box';
  static const String cacheBox = 'cache_box';

  // Animation Durations
  static const Duration shortAnimationDuration = Duration(milliseconds: 200);
  static const Duration mediumAnimationDuration = Duration(milliseconds: 400);
  static const Duration longAnimationDuration = Duration(milliseconds: 600);

  // UI Constants
  static const double borderRadius = 12.0;
  static const double smallBorderRadius = 8.0;
  static const double largeBorderRadius = 16.0;

  static const double defaultPadding = 16.0;
  static const double smallPadding = 8.0;
  static const double largePadding = 24.0;

  static const double defaultMargin = 16.0;
  static const double smallMargin = 8.0;
  static const double largeMargin = 24.0;

  // Text Sizes
  static const double headlineTextSize = 24.0;
  static const double titleTextSize = 20.0;
  static const double subtitleTextSize = 16.0;
  static const double bodyTextSize = 14.0;
  static const double captionTextSize = 12.0;
  static const double smallTextSize = 10.0;

  // Icon Sizes
  static const double smallIconSize = 16.0;
  static const double defaultIconSize = 24.0;
  static const double largeIconSize = 32.0;
  static const double extraLargeIconSize = 48.0;

  // Avatar Sizes
  static const double smallAvatarSize = 24.0;
  static const double defaultAvatarSize = 40.0;
  static const double largeAvatarSize = 56.0;
  static const double extraLargeAvatarSize = 80.0;

  // File Upload Limits
  static const int maxFileSize = 10 * 1024 * 1024; // 10MB
  static const int maxImageSize = 5 * 1024 * 1024; // 5MB
  static const List<String> allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  static const List<String> allowedFileTypes = [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'csv', 'zip', 'rar', '7z'
  ];

  // Message Limits
  static const int maxMessageLength = 4000;
  static const int maxChannelNameLength = 50;
  static const int maxChannelDescriptionLength = 500;
  static const int maxStatusMessageLength = 100;

  // Pagination
  static const int defaultPageSize = 20;
  static const int maxPageSize = 50;

  // Cache Durations
  static const Duration shortCacheDuration = Duration(minutes: 5);
  static const Duration mediumCacheDuration = Duration(hours: 1);
  static const Duration longCacheDuration = Duration(days: 1);

  // Network Retry
  static const int maxRetryAttempts = 3;
  static const Duration retryDelay = Duration(seconds: 2);

  // Typing Indicator
  static const Duration typingTimeout = Duration(seconds: 3);
  static const Duration typingThrottleDelay = Duration(milliseconds: 500);

  // Presence Status
  static const List<String> presenceStatuses = [
    'online',
    'away',
    'dnd',
    'offline'
  ];

  // Channel Types
  static const List<String> channelTypes = [
    'public',
    'private',
    'direct'
  ];

  // Message Types
  static const List<String> messageTypes = [
    'text',
    'image',
    'file',
    'audio',
    'video',
    'system'
  ];

  // Call Types
  static const List<String> callTypes = [
    'audio',
    'video'
  ];

  // Notification Types
  static const List<String> notificationTypes = [
    'message',
    'mention',
    'channel_invite',
    'call',
    'system'
  ];

  // Date Formats
  static const String dateFormat = 'yyyy-MM-dd';
  static const String timeFormat = 'HH:mm';
  static const String dateTimeFormat = 'yyyy-MM-dd HH:mm';
  static const String displayDateFormat = 'MMM dd, yyyy';
  static const String displayTimeFormat = 'h:mm a';
  static const String displayDateTimeFormat = 'MMM dd, yyyy h:mm a';

  // Regular Expressions
  static const String emailRegex =
      r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$';
  static const String phoneRegex =
      r'^\+?[\d\s\-\(\)]{7,15}$';
  static const String urlRegex =
      r'https?://(?:[-\w.])+(?::[0-9]+)?(?:/(?:[\w/_.])*(?:\?(?:[\w&=%.])*)?(?:#(?:[\w.])*)?)?';
  static const String mentionRegex = r'@(\w+)';
  static const String hashtagRegex = r'#(\w+)';

  // Error Messages
  static const String networkErrorMessage = 'Network connection failed. Please check your internet connection.';
  static const String serverErrorMessage = 'Server error occurred. Please try again later.';
  static const String authErrorMessage = 'Authentication failed. Please login again.';
  static const String validationErrorMessage = 'Please check your input and try again.';
  static const String fileUploadErrorMessage = 'File upload failed. Please try again.';
  static const String permissionErrorMessage = 'Permission denied. Please grant required permissions.';

  // Success Messages
  static const String loginSuccessMessage = 'Successfully logged in!';
  static const String logoutSuccessMessage = 'Successfully logged out!';
  static const String profileUpdateSuccessMessage = 'Profile updated successfully!';
  static const String passwordChangeSuccessMessage = 'Password changed successfully!';
  static const String fileUploadSuccessMessage = 'File uploaded successfully!';

  // Feature Flags
  static const bool enableVoiceCalls = true;
  static const bool enableVideoCalls = true;
  static const bool enableFileSharing = true;
  static const bool enableScreenSharing = false;
  static const bool enableMessageReactions = true;
  static const bool enableMessageThreads = false;
  static const bool enableDarkMode = true;
  static const bool enableBiometricAuth = true;
  static const bool enablePushNotifications = true;
  static const bool enableAnalytics = false;
}