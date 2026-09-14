import 'package:flutter/material.dart';

/// Soft elevation only — ported from Laravel's `--shadow-card`/`-float`/`-pop`
/// CSS values. Deliberately not Material's default `elevation`, which reads
/// visually heavier than these.
abstract final class AppShadows {
  static const card = <BoxShadow>[
    BoxShadow(color: Color(0x0A0F172A), offset: Offset(0, 1), blurRadius: 2),
    BoxShadow(color: Color(0x0F0F172A), offset: Offset(0, 1), blurRadius: 3),
  ];

  static const float = <BoxShadow>[
    BoxShadow(
      color: Color(0x40012739),
      offset: Offset(0, 10),
      blurRadius: 30,
      spreadRadius: -10,
    ),
    BoxShadow(color: Color(0x0F0F172A), offset: Offset(0, 2), blurRadius: 6),
  ];

  static const pop = <BoxShadow>[
    BoxShadow(
      color: Color(0x59012739),
      offset: Offset(0, 20),
      blurRadius: 40,
      spreadRadius: -12,
    ),
  ];
}
