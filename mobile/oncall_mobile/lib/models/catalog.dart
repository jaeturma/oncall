class Service {
  Service({
    required this.id,
    required this.name,
    required this.slug,
    this.categoryId,
  });

  factory Service.fromJson(Map<String, dynamic> json) => Service(
    id: json['id'] as int,
    name: json['name'] as String,
    slug: json['slug'] as String,
    categoryId: json['category_id'] as int?,
  );

  final int id;
  final String name;
  final String slug;
  final int? categoryId;
}

class ServiceCategory {
  ServiceCategory({
    required this.id,
    required this.name,
    required this.slug,
    required this.services,
  });

  factory ServiceCategory.fromJson(Map<String, dynamic> json) =>
      ServiceCategory(
        id: json['id'] as int,
        name: json['name'] as String,
        slug: json['slug'] as String,
        services: (json['services'] as List<dynamic>? ?? [])
            .map((s) => Service.fromJson(s as Map<String, dynamic>))
            .toList(),
      );

  final int id;
  final String name;
  final String slug;
  final List<Service> services;
}

class Province {
  Province({required this.id, required this.name});

  factory Province.fromJson(Map<String, dynamic> json) =>
      Province(id: json['id'] as int, name: json['name'] as String);

  final int id;
  final String name;
}

class Municipality {
  Municipality({required this.id, required this.name, required this.type});

  factory Municipality.fromJson(Map<String, dynamic> json) => Municipality(
    id: json['id'] as int,
    name: json['name'] as String,
    type: json['type'] as String? ?? 'municipality',
  );

  final int id;
  final String name;
  final String type;
}
