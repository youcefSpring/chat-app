import 'package:equatable/equatable.dart';

class User extends Equatable {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? avatar;
  final String? bio;
  final String? statusMessage;
  final String presenceStatus;
  final String role;
  final int organizationId;
  final String? timezone;
  final DateTime? lastSeenAt;
  final DateTime createdAt;
  final DateTime updatedAt;
  final bool emailVerified;
  final bool twoFactorEnabled;
  final Map<String, dynamic>? notificationSettings;

  const User({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.avatar,
    this.bio,
    this.statusMessage,
    required this.presenceStatus,
    required this.role,
    required this.organizationId,
    this.timezone,
    this.lastSeenAt,
    required this.createdAt,
    required this.updatedAt,
    required this.emailVerified,
    required this.twoFactorEnabled,
    this.notificationSettings,
  });

  bool get isOnline => presenceStatus == 'online';
  bool get isAway => presenceStatus == 'away';
  bool get isDoNotDisturb => presenceStatus == 'dnd';
  bool get isOffline => presenceStatus == 'offline';
  bool get isAdmin => role == 'admin';
  bool get isMember => role == 'member';

  String get displayName => name.isNotEmpty ? name : email.split('@').first;

  String get initials {
    final parts = name.split(' ');
    if (parts.length >= 2) {
      return '${parts.first[0]}${parts.last[0]}'.toUpperCase();
    }
    return name.isNotEmpty ? name[0].toUpperCase() : email[0].toUpperCase();
  }

  User copyWith({
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
    return User(
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

  @override
  List<Object?> get props => [
        id,
        name,
        email,
        phone,
        avatar,
        bio,
        statusMessage,
        presenceStatus,
        role,
        organizationId,
        timezone,
        lastSeenAt,
        createdAt,
        updatedAt,
        emailVerified,
        twoFactorEnabled,
        notificationSettings,
      ];
}