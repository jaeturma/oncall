/// Small null-safe parsing helpers shared by every model's `fromJson`. The
/// API returns decimals as strings (Laravel's `decimal:2` cast) and dates as
/// ISO-8601 strings or null, so every field needs one of these rather than a
/// raw cast.
DateTime? parseDate(dynamic value) =>
    value == null ? null : DateTime.tryParse(value as String);

double? parseNum(dynamic value) {
  if (value == null) {
    return null;
  }
  if (value is num) {
    return value.toDouble();
  }

  return double.tryParse(value.toString());
}

int? parseInt(dynamic value) => value == null
    ? null
    : (value is int ? value : int.tryParse(value.toString()));

/// A `{id, name}` reference embedded in a resource (e.g. `service`, `province`).
class IdName {
  IdName({required this.id, required this.name});

  factory IdName.fromJson(Map<String, dynamic> json) =>
      IdName(id: json['id'] as int, name: json['name'] as String);

  static IdName? fromJsonOrNull(dynamic json) =>
      json == null ? null : IdName.fromJson(json as Map<String, dynamic>);

  final int id;
  final String name;
}
