import 'package:equatable/equatable.dart';
import 'user.dart';
import 'organization.dart';

class AuthResult extends Equatable {
  final String accessToken;
  final String refreshToken;
  final User user;
  final Organization organization;
  final int expiresIn;
  final String tokenType;

  const AuthResult({
    required this.accessToken,
    required this.refreshToken,
    required this.user,
    required this.organization,
    required this.expiresIn,
    required this.tokenType,
  });

  bool get isValid => accessToken.isNotEmpty && refreshToken.isNotEmpty;

  DateTime get expirationTime => DateTime.now().add(Duration(seconds: expiresIn));

  AuthResult copyWith({
    String? accessToken,
    String? refreshToken,
    User? user,
    Organization? organization,
    int? expiresIn,
    String? tokenType,
  }) {
    return AuthResult(
      accessToken: accessToken ?? this.accessToken,
      refreshToken: refreshToken ?? this.refreshToken,
      user: user ?? this.user,
      organization: organization ?? this.organization,
      expiresIn: expiresIn ?? this.expiresIn,
      tokenType: tokenType ?? this.tokenType,
    );
  }

  @override
  List<Object?> get props => [
        accessToken,
        refreshToken,
        user,
        organization,
        expiresIn,
        tokenType,
      ];
}