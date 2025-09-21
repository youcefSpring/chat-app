import 'package:json_annotation/json_annotation.dart';
import '../../domain/entities/user.dart';

part 'user_model.g.dart';

@JsonSerializable()
class UserModel extends User {
  const UserModel({
    required super.id,
    required super.name,
    required super.email,
    super.phone,
    super.avatar,
    super.bio,
    super.statusMessage,
    required super.presenceStatus,
    required super.role,
    required super.organizationId,
    super.timezone,
    super.lastSeenAt,
    required super.createdAt,
    required super.updatedAt,
    required super.emailVerified,
    required super.twoFactorEnabled,
    super.notificationSettings,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) =>
      _$UserModelFromJson(json);

  Map<String, dynamic> toJson() => _$UserModelToJson(this);

  factory UserModel.fromEntity(User user) {
    return UserModel(
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
      avatar: user.avatar,
      bio: user.bio,
      statusMessage: user.statusMessage,
      presenceStatus: user.presenceStatus,
      role: user.role,
      organizationId: user.organizationId,
      timezone: user.timezone,
      lastSeenAt: user.lastSeenAt,
      createdAt: user.createdAt,
      updatedAt: user.updatedAt,
      emailVerified: user.emailVerified,
      twoFactorEnabled: user.twoFactorEnabled,
      notificationSettings: user.notificationSettings,
    );
  }

  User toEntity() {
    return User(
      id: id,
      name: name,
      email: email,
      phone: phone,
      avatar: avatar,
      bio: bio,
      statusMessage: statusMessage,
      presenceStatus: presenceStatus,
      role: role,
      organizationId: organizationId,
      timezone: timezone,
      lastSeenAt: lastSeenAt,
      createdAt: createdAt,
      updatedAt: updatedAt,
      emailVerified: emailVerified,
      twoFactorEnabled: twoFactorEnabled,
      notificationSettings: notificationSettings,
    );
  }

  UserModel copyWith({
    int? id,
    String? name,
    String? email,
    String? phone,
    String? avatar,
    String? bio,
    String? statusMessage,
    String? presenceStatus,
    String? role,
    int? organizationId,
    String? timezone,
    DateTime? lastSeenAt,
    DateTime? createdAt,
    DateTime? updatedAt,
    bool? emailVerified,
    bool? twoFactorEnabled,
    Map<String, dynamic>? notificationSettings,
  }) {
    return UserModel(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      avatar: avatar ?? this.avatar,
      bio: bio ?? this.bio,
      statusMessage: statusMessage ?? this.statusMessage,
      presenceStatus: presenceStatus ?? this.presenceStatus,
      role: role ?? this.role,
      organizationId: organizationId ?? this.organizationId,
      timezone: timezone ?? this.timezone,
      lastSeenAt: lastSeenAt ?? this.lastSeenAt,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      emailVerified: emailVerified ?? this.emailVerified,
      twoFactorEnabled: twoFactorEnabled ?? this.twoFactorEnabled,
      notificationSettings: notificationSettings ?? this.notificationSettings,
    );
  }
}