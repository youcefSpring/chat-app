class ApiConstants {
  // Base URLs
  static const String baseUrl = 'http://localhost:8000';
  static const String apiUrl = '$baseUrl/api';
  static const String socketUrl = '$baseUrl';

  // API Endpoints
  static const String login = '/auth/login';
  static const String register = '/auth/register';
  static const String logout = '/auth/logout';
  static const String refreshToken = '/auth/refresh';
  static const String forgotPassword = '/auth/forgot-password';
  static const String resetPassword = '/auth/reset-password';

  // User endpoints
  static const String userProfile = '/user/profile';
  static const String updateProfile = '/user/profile';
  static const String changePassword = '/user/change-password';
  static const String userPresence = '/user/presence';

  // Organization endpoints
  static const String organizations = '/organizations';
  static const String organizationMembers = '/organizations/{id}/members';
  static const String organizationChannels = '/organizations/{id}/channels';

  // Channel endpoints
  static const String channels = '/channels';
  static const String channelMessages = '/channels/{id}/messages';
  static const String channelMembers = '/channels/{id}/members';
  static const String joinChannel = '/channels/{id}/join';
  static const String leaveChannel = '/channels/{id}/leave';
  static const String channelTyping = '/channels/{id}/typing';

  // Message endpoints
  static const String messages = '/messages';
  static const String messageReactions = '/messages/{id}/reactions';
  static const String markAsRead = '/messages/{id}/read';
  static const String editMessage = '/messages/{id}';
  static const String deleteMessage = '/messages/{id}';

  // File endpoints
  static const String uploadFile = '/files/upload';
  static const String downloadFile = '/files/{id}/download';
  static const String deleteFile = '/files/{id}';

  // Direct messages
  static const String directMessages = '/direct-messages';
  static const String createDirectMessage = '/direct-messages';

  // Call endpoints
  static const String calls = '/calls';
  static const String startCall = '/calls/start';
  static const String joinCall = '/calls/{id}/join';
  static const String endCall = '/calls/{id}/end';

  // Search endpoints
  static const String searchMessages = '/search/messages';
  static const String searchUsers = '/search/users';
  static const String searchChannels = '/search/channels';

  // Notification endpoints
  static const String notifications = '/notifications';
  static const String markNotificationRead = '/notifications/{id}/read';
  static const String notificationSettings = '/notifications/settings';

  // Admin endpoints
  static const String adminDashboard = '/admin/dashboard';
  static const String adminUsers = '/admin/users';
  static const String adminChannels = '/admin/channels';
  static const String adminAuditLogs = '/admin/audit-logs';

  // WebSocket events
  static const String eventMessageSent = 'message.sent';
  static const String eventMessageReceived = 'message.received';
  static const String eventTypingStart = 'typing.start';
  static const String eventTypingStop = 'typing.stop';
  static const String eventUserOnline = 'user.online';
  static const String eventUserOffline = 'user.offline';
  static const String eventChannelJoined = 'channel.joined';
  static const String eventChannelLeft = 'channel.left';
  static const String eventCallStarted = 'call.started';
  static const String eventCallEnded = 'call.ended';
  static const String eventPresenceUpdated = 'presence.updated';

  // Headers
  static const String authorizationHeader = 'Authorization';
  static const String contentTypeHeader = 'Content-Type';
  static const String acceptHeader = 'Accept';

  // Content Types
  static const String applicationJson = 'application/json';
  static const String multipartFormData = 'multipart/form-data';

  // Request timeouts (in milliseconds)
  static const int connectTimeout = 30000;
  static const int receiveTimeout = 30000;
  static const int sendTimeout = 30000;
}