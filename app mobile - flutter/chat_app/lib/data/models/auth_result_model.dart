import 'package:json_annotation/json_annotation.dart';
import '../../domain/entities/auth_result.dart';
import 'user_model.dart';
import 'organization_model.dart';

part 'auth_result_model.g.dart';

@JsonSerializable()
class AuthResultModel extends AuthResult {
  @JsonKey(name: 'access_token')
  final String accessTokenField;

  @JsonKey(name: 'refresh_token')
  final String refreshTokenField;

  @JsonKey(name: 'expires_in')
  final int expiresInField;

  @JsonKey(name: 'token_type')
  final String tokenTypeField;

  const AuthResultModel({
    required this.accessTokenField,
    required this.refreshTokenField,
    required UserModel user,
    required OrganizationModel organization,
    required this.expiresInField,
    required this.tokenTypeField,
  }) : super(
          accessToken: accessTokenField,
          refreshToken: refreshTokenField,
          user: user,
          organization: organization,
          expiresIn: expiresInField,
          tokenType: tokenTypeField,
        );

  factory AuthResultModel.fromJson(Map<String, dynamic> json) =>
      _$AuthResultModelFromJson(json);

  Map<String, dynamic> toJson() => _$AuthResultModelToJson(this);

  factory AuthResultModel.fromEntity(AuthResult authResult) {
    return AuthResultModel(
      accessTokenField: authResult.accessToken,
      refreshTokenField: authResult.refreshToken,
      user: UserModel.fromEntity(authResult.user),
      organization: OrganizationModel.fromEntity(authResult.organization),
      expiresInField: authResult.expiresIn,
      tokenTypeField: authResult.tokenType,
    );
  }

  AuthResult toEntity() {
    return AuthResult(
      accessToken: accessTokenField,
      refreshToken: refreshTokenField,
      user: (user as UserModel).toEntity(),
      organization: (organization as OrganizationModel).toEntity(),
      expiresIn: expiresInField,
      tokenType: tokenTypeField,
    );
  }

  AuthResultModel copyWith({
    String? accessToken,
    String? refreshToken,
    UserModel? user,
    OrganizationModel? organization,
    int? expiresIn,
    String? tokenType,
  }) {
    return AuthResultModel(
      accessTokenField: accessToken ?? accessTokenField,
      refreshTokenField: refreshToken ?? refreshTokenField,
      user: user ?? this.user as UserModel,
      organization: organization ?? this.organization as OrganizationModel,
      expiresInField: expiresIn ?? expiresInField,
      tokenTypeField: tokenType ?? tokenTypeField,
    );
  }
}