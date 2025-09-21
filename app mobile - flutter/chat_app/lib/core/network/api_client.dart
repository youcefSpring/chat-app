import 'package:dio/dio.dart';
import 'package:retrofit/retrofit.dart';
import '../constants/api_constants.dart';

part 'api_client.g.dart';

@RestApi(baseUrl: ApiConstants.apiUrl)
abstract class ApiClient {
  factory ApiClient(Dio dio, {String baseUrl}) = _ApiClient;

  // Authentication endpoints
  @POST(ApiConstants.login)
  Future<HttpResponse<Map<String, dynamic>>> login(
    @Body() Map<String, dynamic> loginData,
  );

  @POST(ApiConstants.register)
  Future<HttpResponse<Map<String, dynamic>>> register(
    @Body() Map<String, dynamic> registerData,
  );

  @POST(ApiConstants.logout)
  Future<HttpResponse<Map<String, dynamic>>> logout();

  @POST(ApiConstants.refreshToken)
  Future<HttpResponse<Map<String, dynamic>>> refreshToken(
    @Body() Map<String, dynamic> tokenData,
  );

  @POST(ApiConstants.forgotPassword)
  Future<HttpResponse<Map<String, dynamic>>> forgotPassword(
    @Body() Map<String, dynamic> emailData,
  );

  @POST(ApiConstants.resetPassword)
  Future<HttpResponse<Map<String, dynamic>>> resetPassword(
    @Body() Map<String, dynamic> resetData,
  );

  // User endpoints
  @GET(ApiConstants.userProfile)
  Future<HttpResponse<Map<String, dynamic>>> getUserProfile();

  @PUT(ApiConstants.updateProfile)
  Future<HttpResponse<Map<String, dynamic>>> updateProfile(
    @Body() Map<String, dynamic> profileData,
  );

  @PATCH(ApiConstants.changePassword)
  Future<HttpResponse<Map<String, dynamic>>> changePassword(
    @Body() Map<String, dynamic> passwordData,
  );

  @PATCH(ApiConstants.userPresence)
  Future<HttpResponse<Map<String, dynamic>>> updatePresence(
    @Body() Map<String, dynamic> presenceData,
  );

  // Organization endpoints
  @GET(ApiConstants.organizations)
  Future<HttpResponse<Map<String, dynamic>>> getOrganizations();

  @GET('/organizations/{id}/members')
  Future<HttpResponse<Map<String, dynamic>>> getOrganizationMembers(
    @Path('id') int organizationId,
  );

  @GET('/organizations/{id}/channels')
  Future<HttpResponse<Map<String, dynamic>>> getOrganizationChannels(
    @Path('id') int organizationId,
  );

  // Channel endpoints
  @GET(ApiConstants.channels)
  Future<HttpResponse<Map<String, dynamic>>> getChannels();

  @POST(ApiConstants.channels)
  Future<HttpResponse<Map<String, dynamic>>> createChannel(
    @Body() Map<String, dynamic> channelData,
  );

  @GET('/channels/{id}')
  Future<HttpResponse<Map<String, dynamic>>> getChannel(
    @Path('id') int channelId,
  );

  @PUT('/channels/{id}')
  Future<HttpResponse<Map<String, dynamic>>> updateChannel(
    @Path('id') int channelId,
    @Body() Map<String, dynamic> channelData,
  );

  @DELETE('/channels/{id}')
  Future<HttpResponse<Map<String, dynamic>>> deleteChannel(
    @Path('id') int channelId,
  );

  @GET('/channels/{id}/messages')
  Future<HttpResponse<Map<String, dynamic>>> getChannelMessages(
    @Path('id') int channelId,
    @Query('page') int? page,
    @Query('limit') int? limit,
    @Query('before') String? before,
  );

  @GET('/channels/{id}/members')
  Future<HttpResponse<Map<String, dynamic>>> getChannelMembers(
    @Path('id') int channelId,
  );

  @POST('/channels/{id}/members')
  Future<HttpResponse<Map<String, dynamic>>> addChannelMembers(
    @Path('id') int channelId,
    @Body() Map<String, dynamic> membersData,
  );

  @DELETE('/channels/{id}/members/{userId}')
  Future<HttpResponse<Map<String, dynamic>>> removeChannelMember(
    @Path('id') int channelId,
    @Path('userId') int userId,
  );

  @POST('/channels/{id}/join')
  Future<HttpResponse<Map<String, dynamic>>> joinChannel(
    @Path('id') int channelId,
  );

  @POST('/channels/{id}/leave')
  Future<HttpResponse<Map<String, dynamic>>> leaveChannel(
    @Path('id') int channelId,
  );

  @POST('/channels/{id}/typing')
  Future<HttpResponse<Map<String, dynamic>>> sendTypingIndicator(
    @Path('id') int channelId,
  );

  // Message endpoints
  @POST(ApiConstants.messages)
  Future<HttpResponse<Map<String, dynamic>>> sendMessage(
    @Body() Map<String, dynamic> messageData,
  );

  @GET('/messages/{id}')
  Future<HttpResponse<Map<String, dynamic>>> getMessage(
    @Path('id') int messageId,
  );

  @PUT('/messages/{id}')
  Future<HttpResponse<Map<String, dynamic>>> updateMessage(
    @Path('id') int messageId,
    @Body() Map<String, dynamic> messageData,
  );

  @DELETE('/messages/{id}')
  Future<HttpResponse<Map<String, dynamic>>> deleteMessage(
    @Path('id') int messageId,
  );

  @POST('/messages/{id}/reactions')
  Future<HttpResponse<Map<String, dynamic>>> addReaction(
    @Path('id') int messageId,
    @Body() Map<String, dynamic> reactionData,
  );

  @DELETE('/messages/{id}/reactions/{reactionId}')
  Future<HttpResponse<Map<String, dynamic>>> removeReaction(
    @Path('id') int messageId,
    @Path('reactionId') int reactionId,
  );

  @POST('/messages/{id}/read')
  Future<HttpResponse<Map<String, dynamic>>> markMessageAsRead(
    @Path('id') int messageId,
  );

  // File endpoints
  @POST(ApiConstants.uploadFile)
  @MultiPart()
  Future<HttpResponse<Map<String, dynamic>>> uploadFile(
    @Part() List<int> file,
    @Part() String fileName,
    @Part() String? channelId,
    @Part() String? messageId,
  );

  @GET('/files/{id}/download')
  Future<HttpResponse<List<int>>> downloadFile(
    @Path('id') int fileId,
  );

  @DELETE('/files/{id}')
  Future<HttpResponse<Map<String, dynamic>>> deleteFile(
    @Path('id') int fileId,
  );

  // Direct message endpoints
  @GET(ApiConstants.directMessages)
  Future<HttpResponse<Map<String, dynamic>>> getDirectMessages();

  @POST(ApiConstants.createDirectMessage)
  Future<HttpResponse<Map<String, dynamic>>> createDirectMessage(
    @Body() Map<String, dynamic> directMessageData,
  );

  // Call endpoints
  @GET(ApiConstants.calls)
  Future<HttpResponse<Map<String, dynamic>>> getCalls();

  @POST(ApiConstants.startCall)
  Future<HttpResponse<Map<String, dynamic>>> startCall(
    @Body() Map<String, dynamic> callData,
  );

  @POST('/calls/{id}/join')
  Future<HttpResponse<Map<String, dynamic>>> joinCall(
    @Path('id') int callId,
  );

  @POST('/calls/{id}/end')
  Future<HttpResponse<Map<String, dynamic>>> endCall(
    @Path('id') int callId,
  );

  // Search endpoints
  @GET(ApiConstants.searchMessages)
  Future<HttpResponse<Map<String, dynamic>>> searchMessages(
    @Query('q') String query,
    @Query('channelId') int? channelId,
    @Query('page') int? page,
    @Query('limit') int? limit,
  );

  @GET(ApiConstants.searchUsers)
  Future<HttpResponse<Map<String, dynamic>>> searchUsers(
    @Query('q') String query,
    @Query('organizationId') int? organizationId,
  );

  @GET(ApiConstants.searchChannels)
  Future<HttpResponse<Map<String, dynamic>>> searchChannels(
    @Query('q') String query,
    @Query('organizationId') int? organizationId,
  );

  // Notification endpoints
  @GET(ApiConstants.notifications)
  Future<HttpResponse<Map<String, dynamic>>> getNotifications(
    @Query('page') int? page,
    @Query('limit') int? limit,
    @Query('unread') bool? unreadOnly,
  );

  @POST('/notifications/{id}/read')
  Future<HttpResponse<Map<String, dynamic>>> markNotificationAsRead(
    @Path('id') int notificationId,
  );

  @GET(ApiConstants.notificationSettings)
  Future<HttpResponse<Map<String, dynamic>>> getNotificationSettings();

  @PUT(ApiConstants.notificationSettings)
  Future<HttpResponse<Map<String, dynamic>>> updateNotificationSettings(
    @Body() Map<String, dynamic> settingsData,
  );

  // Admin endpoints
  @GET(ApiConstants.adminDashboard)
  Future<HttpResponse<Map<String, dynamic>>> getAdminDashboard();

  @GET(ApiConstants.adminUsers)
  Future<HttpResponse<Map<String, dynamic>>> getAdminUsers(
    @Query('page') int? page,
    @Query('limit') int? limit,
    @Query('search') String? search,
  );

  @GET(ApiConstants.adminChannels)
  Future<HttpResponse<Map<String, dynamic>>> getAdminChannels(
    @Query('page') int? page,
    @Query('limit') int? limit,
    @Query('search') String? search,
  );

  @GET(ApiConstants.adminAuditLogs)
  Future<HttpResponse<Map<String, dynamic>>> getAdminAuditLogs(
    @Query('page') int? page,
    @Query('limit') int? limit,
    @Query('action') String? action,
    @Query('userId') int? userId,
  );
}