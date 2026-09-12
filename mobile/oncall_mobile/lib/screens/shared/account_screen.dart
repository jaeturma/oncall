import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../state/auth_state.dart';
import '../../widgets/common.dart';

class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final authState = context.watch<AuthState>();
    final user = authState.user;

    return Scaffold(
      appBar: AppBar(title: const Text('Account')),
      body: ListView(
        children: [
          ListTile(
            leading: CircleAvatar(child: Text(user?.initials ?? '?')),
            title: Text(user?.name ?? ''),
            subtitle: Text(user?.email ?? ''),
            trailing: user == null
                ? null
                : Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      if (user.rating != null)
                        Text('★ ${user.rating!.toStringAsFixed(1)}'),
                      StatusChip(user.identityVerificationStatus ?? 'PENDING'),
                    ],
                  ),
          ),
          const Divider(),
          if (user?.isProvider == true)
            ListTile(
              leading: const Icon(Icons.storefront_outlined),
              title: const Text('Provider profile'),
              onTap: () => context.push('/provider-profile/edit'),
            ),
          ListTile(
            leading: const Icon(Icons.badge_outlined),
            title: const Text('Verification'),
            onTap: () => context.push('/verification'),
          ),
          ListTile(
            leading: const Icon(Icons.notifications_outlined),
            title: const Text('Notifications'),
            onTap: () => context.push('/notifications'),
          ),
          ListTile(
            leading: const Icon(Icons.groups_outlined),
            title: const Text('Sponsored users'),
            onTap: () => context.push('/sponsor'),
          ),
          ListTile(
            leading: const Icon(Icons.shield_outlined),
            title: const Text('Account notices'),
            onTap: () => context.push('/enforcement-cases'),
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.logout, color: Colors.red),
            title: const Text('Log out', style: TextStyle(color: Colors.red)),
            onTap: () => authState.logout(),
          ),
        ],
      ),
    );
  }
}
