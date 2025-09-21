import 'package:equatable/equatable.dart';

class Organization extends Equatable {
  final int id;
  final String name;
  final String? description;
  final String? logo;
  final String? website;
  final Map<String, dynamic>? settings;
  final DateTime createdAt;
  final DateTime updatedAt;
  final int membersCount;
  final int channelsCount;
  final int? createdBy;

  const Organization({
    required this.id,
    required this.name,
    this.description,
    this.logo,
    this.website,
    this.settings,
    required this.createdAt,
    required this.updatedAt,
    required this.membersCount,
    required this.channelsCount,
    this.createdBy,
  });

  String get displayName => name.isNotEmpty ? name : 'Organization';

  String get initials {
    final parts = name.split(' ');
    if (parts.length >= 2) {
      return '${parts.first[0]}${parts.last[0]}'.toUpperCase();
    }
    return name.isNotEmpty ? name[0].toUpperCase() : 'O';
  }

  bool get hasLogo => logo != null && logo!.isNotEmpty;
  bool get hasWebsite => website != null && website!.isNotEmpty;
  bool get hasDescription => description != null && description!.isNotEmpty;

  Organization copyWith({
    int? id,
    String? name,
    String? description,
    String? logo,
    String? website,
    Map<String, dynamic>? settings,
    DateTime? createdAt,
    DateTime? updatedAt,
    int? membersCount,
    int? channelsCount,
    int? createdBy,
  }) {
    return Organization(
      id: id ?? this.id,
      name: name ?? this.name,
      description: description ?? this.description,
      logo: logo ?? this.logo,
      website: website ?? this.website,
      settings: settings ?? this.settings,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      membersCount: membersCount ?? this.membersCount,
      channelsCount: channelsCount ?? this.channelsCount,
      createdBy: createdBy ?? this.createdBy,
    );
  }

  @override
  List<Object?> get props => [
        id,
        name,
        description,
        logo,
        website,
        settings,
        createdAt,
        updatedAt,
        membersCount,
        channelsCount,
        createdBy,
      ];
}