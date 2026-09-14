import 'package:flutter/material.dart';

import 'app.dart';
import 'services/push_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Must happen before runApp(): registers the background message handler
  // ahead of any message that could arrive. A no-op until a real Firebase
  // project is configured (see mobile/oncall_mobile's Phase M push scoping).
  await PushService.initializeFirebase();
  runApp(const OncallApp());
}
