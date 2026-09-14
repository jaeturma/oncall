import 'dart:math';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// A stable, app-generated identifier for this installation — never a
/// hardware/device identifier (Phase M Step 6). Generated once and reused
/// for the life of the install; a reinstall gets a new one, which is fine —
/// the backend's `devices/register` treats it like any other new install.
class DeviceIdentity {
  DeviceIdentity() : _storage = const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  static const _key = 'oncall_installation_id';

  Future<String> installationId() async {
    final existing = await _storage.read(key: _key);
    if (existing != null && existing.isNotEmpty) {
      return existing;
    }

    final generated = _generate();
    await _storage.write(key: _key, value: generated);

    return generated;
  }

  /// 32 random hex characters — well within the backend's 64-char limit and
  /// its `^[a-zA-Z0-9_-]+$` validation, with no formatting to get wrong.
  String _generate() {
    final random = Random.secure();
    final bytes = List<int>.generate(16, (_) => random.nextInt(256));

    return bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
  }
}
