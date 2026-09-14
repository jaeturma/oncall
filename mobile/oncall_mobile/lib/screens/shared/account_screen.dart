import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../state/auth_state.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_card.dart';
import '../../widgets/avatar.dart';
import '../../widgets/common.dart';
import '../../widgets/eyebrow_text.dart';
import '../../widgets/rating_summary.dart';

class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final authState = context.watch<AuthState>();
    final user = authState.user;

    return Scaffold(
      appBar: AppBar(title: const Text('Account')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          AppCard(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Avatar(name: user?.name, size: AvatarSize.lg),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        user?.name ?? '',
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 17,
                          fontWeight: FontWeight.w600,
                          color: AppColors.ink,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        user?.email ?? '',
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 13,
                          color: AppColors.inkSecondary,
                        ),
                      ),
                      if (user != null) ...[
                        const SizedBox(height: 8),
                        Wrap(
                          crossAxisAlignment: WrapCrossAlignment.center,
                          spacing: 10,
                          runSpacing: 6,
                          children: [
                            if (user.rating != null)
                              RatingSummary(rating: user.rating),
                            StatusChip(
                              user.identityVerificationStatus ?? 'PENDING',
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          const Padding(
            padding: EdgeInsets.only(left: 4, bottom: 8),
            child: EyebrowText('Account'),
          ),
          AppCard(
            padding: EdgeInsets.zero,
            child: Column(
              children: [
                if (user?.isProvider == true)
                  _AccountTile(
                    icon: Icons.storefront_outlined,
                    label: 'Provider profile',
                    onTap: () => context.push('/provider-profile/edit'),
                  ),
                _AccountTile(
                  icon: Icons.badge_outlined,
                  label: 'Verification',
                  onTap: () => context.push('/verification'),
                ),
                _AccountTile(
                  icon: Icons.notifications_outlined,
                  label: 'Notifications',
                  onTap: () => context.push('/notifications'),
                ),
                _AccountTile(
                  icon: Icons.groups_outlined,
                  label: 'Sponsored users',
                  onTap: () => context.push('/sponsor'),
                ),
                _AccountTile(
                  icon: Icons.shield_outlined,
                  label: 'Account notices',
                  onTap: () => context.push('/enforcement-cases'),
                  showDivider: false,
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          AppCard(
            padding: EdgeInsets.zero,
            child: _AccountTile(
              icon: Icons.logout,
              label: 'Log out',
              tone: AppColors.danger600,
              onTap: authState.logout,
              showDivider: false,
            ),
          ),
        ],
      ),
    );
  }
}

class _AccountTile extends StatelessWidget {
  const _AccountTile({
    required this.icon,
    required this.label,
    required this.onTap,
    this.tone = AppColors.ink,
    this.showDivider = true,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color tone;
  final bool showDivider;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        ListTile(
          leading: Icon(icon, color: tone),
          title: Text(label, style: TextStyle(color: tone)),
          trailing: const Icon(
            Icons.chevron_right,
            size: 18,
            color: AppColors.inkMuted,
          ),
          onTap: onTap,
        ),
        if (showDivider) const Divider(height: 1, color: AppColors.line),
      ],
    );
  }
}
