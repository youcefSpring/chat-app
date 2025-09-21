import 'package:json_annotation/json_annotation.dart';
import '../../domain/entities/organization.dart';

part 'organization_model.g.dart';

@JsonSerializable()
class OrganizationModel extends Organization {
  const OrganizationModel({
    required super.id,
    required super.name,
    super.description,
    super.logo,
    super.website,
    super.settings,
    required super.createdAt,
    required super.updatedAt,
    required super.membersCount,
    required super.channelsCount,
    super.createdBy,
  });

  factory OrganizationModel.fromJson(Map<String, dynamic> json) =>
      _$OrganizationModelFromJson(json);

  Map<String, dynamic> toJson() => _$OrganizationModelToJson(this);

  factory OrganizationModel.fromEntity(Organization organization) {
    return OrganizationModel(
      id: organization.id,
      name: organization.name,
      description: organization.description,
      logo: organization.logo,
      website: organization.website,
      settings: organization.settings,
      createdAt: organization.createdAt,
      updatedAt: organization.updatedAt,
      membersCount: organization.membersCount,
      channelsCount: organization.channelsCount,
      createdBy: organization.createdBy,
    );
  }

  Organization toEntity() {
    return Organization(
      id: id,
      name: name,
      description: description,
      logo: logo,
      website: website,
      settings: settings,
      createdAt: createdAt,
      updatedAt: updatedAt,
      membersCount: membersCount,
      channelsCount: channelsCount,
      createdBy: createdBy,
    );
  }

  OrganizationModel copyWith({
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
    return OrganizationModel(
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
}