import 'package:flutter/material.dart';

/// Every color the Oncall Philippines brand uses, ported verbatim from the
/// Laravel web app's Tailwind tokens (`resources/css/app.css`). Laravel is
/// the visual source of truth — do not add or adjust a value here without
/// updating it there first.
abstract final class AppColors {
  // Brand: deep navy (trust, authority).
  static const navy50 = Color(0xFFEEF4F7);
  static const navy100 = Color(0xFFD5E2E9);
  static const navy200 = Color(0xFFAEC7D3);
  static const navy300 = Color(0xFF7CA1B4);
  static const navy400 = Color(0xFF4A7488);
  static const navy500 = Color(0xFF2D566B);
  static const navy600 = Color(0xFF1F4255);
  static const navy700 = Color(0xFF163545);
  static const navy800 = Color(0xFF0C2B3B);
  static const navy900 = Color(0xFF01273A);
  static const navy950 = Color(0xFF001824);

  // Accent: warm gold (primary call to action).
  static const gold50 = Color(0xFFFFFAEA);
  static const gold100 = Color(0xFFFFF1C2);
  static const gold200 = Color(0xFFFFE285);
  static const gold300 = Color(0xFFFFD44D);
  static const gold400 = Color(0xFFFFCE10);
  static const gold500 = Color(0xFFF5B400);
  static const gold600 = Color(0xFFD38F00);
  static const gold700 = Color(0xFFA86A02);
  static const gold800 = Color(0xFF8A5308);
  static const gold900 = Color(0xFF74440D);

  // Semantic states (kept deliberately calm, matching Laravel).
  static const success50 = Color(0xFFECFDF5);
  static const success100 = Color(0xFFD1FAE5);
  static const success500 = Color(0xFF10B981);
  static const success600 = Color(0xFF059669);
  static const success700 = Color(0xFF047857);
  static const success800 = Color(0xFF065F46);

  static const warning50 = Color(0xFFFFFBEB);
  static const warning100 = Color(0xFFFEF3C7);
  static const warning500 = Color(0xFFF59E0B);
  static const warning600 = Color(0xFFD97706);
  static const warning700 = Color(0xFFB45309);
  static const warning800 = Color(0xFF92400E);

  static const danger50 = Color(0xFFFEF2F2);
  static const danger100 = Color(0xFFFEE2E2);
  static const danger500 = Color(0xFFEF4444);
  static const danger600 = Color(0xFFDC2626);
  static const danger700 = Color(0xFFB91C1C);
  static const danger800 = Color(0xFF991B1B);

  static const info50 = Color(0xFFEFF6FF);
  static const info100 = Color(0xFFDBEAFE);
  static const info600 = Color(0xFF2563EB);
  static const info700 = Color(0xFF1D4ED8);
  static const info800 = Color(0xFF1E40AF);

  // Neutral surfaces & text.
  static const canvas = Color(0xFFF5F7FA);
  static const surface = Color(0xFFFFFFFF);
  static const surfaceMuted = Color(0xFFF8FAFC);
  static const line = Color(0xFFE2E8F0);
  static const lineStrong = Color(0xFFCBD5E1);
  static const ink = Color(0xFF0F172A);
  static const inkSecondary = Color(0xFF475569);
  static const inkMuted = Color(0xFF64748B);

  // Plain slate, used for the neutral badge/chip tone and the offline dot.
  static const slate100 = Color(0xFFF1F5F9);
  static const slate300 = Color(0xFFCBD5E1);
  static const slate400 = Color(0xFF94A3B8);
  static const slate600 = Color(0xFF475569);
  static const slate700 = Color(0xFF334155);
}
