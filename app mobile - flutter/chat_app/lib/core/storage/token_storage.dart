import 'package:shared_preferences/shared_preferences.dart';
import '../constants/app_constants.dart';

class TokenStorage {
  static TokenStorage? _instance;
  SharedPreferences? _prefs;

  TokenStorage._();

  static TokenStorage get instance {
    _instance ??= TokenStorage._();
    return _instance!;
  }

  Future<void> init() async {
    _prefs ??= await SharedPreferences.getInstance();
  }

  Future<String?> getAccessToken() async {
    await init();
    return _prefs?.getString(AppConstants.tokenKey);
  }

  Future<String?> getRefreshToken() async {
    await init();
    return _prefs?.getString(AppConstants.refreshTokenKey);
  }

  Future<void> saveAccessToken(String token) async {
    await init();
    await _prefs?.setString(AppConstants.tokenKey, token);
  }

  Future<void> saveRefreshToken(String refreshToken) async {
    await init();
    await _prefs?.setString(AppConstants.refreshTokenKey, refreshToken);
  }

  Future<void> saveTokens(String accessToken, String refreshToken) async {
    await init();
    await Future.wait([
      _prefs?.setString(AppConstants.tokenKey, accessToken) ?? Future.value(),
      _prefs?.setString(AppConstants.refreshTokenKey, refreshToken) ?? Future.value(),
    ]);
  }

  Future<void> clearTokens() async {
    await init();
    await Future.wait([
      _prefs?.remove(AppConstants.tokenKey) ?? Future.value(),
      _prefs?.remove(AppConstants.refreshTokenKey) ?? Future.value(),
    ]);
  }

  Future<bool> hasValidTokens() async {
    final accessToken = await getAccessToken();
    final refreshToken = await getRefreshToken();
    return accessToken != null && refreshToken != null;
  }
}