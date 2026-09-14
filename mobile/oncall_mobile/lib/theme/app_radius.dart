import 'package:flutter/material.dart';

/// Corner radii ported from Laravel's `.btn`/`.input` (rounded-lg, 8px),
/// `.card` (rounded-2xl, 16px), and `.badge`/`.choice` (fully round / 12px).
abstract final class AppRadius {
  static const double control = 8;
  static const double card = 16;
  static const double choice = 12;

  static const controlRadius = BorderRadius.all(Radius.circular(control));
  static const cardRadius = BorderRadius.all(Radius.circular(card));
  static const choiceRadius = BorderRadius.all(Radius.circular(choice));
  static const pillRadius = BorderRadius.all(Radius.circular(999));
}
