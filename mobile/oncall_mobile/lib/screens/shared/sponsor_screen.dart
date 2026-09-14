import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/sponsor_repository.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_card.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/common.dart';
import '../../widgets/stat_card.dart';

class SponsorScreen extends StatefulWidget {
  const SponsorScreen({super.key});

  @override
  State<SponsorScreen> createState() => _SponsorScreenState();
}

class _SponsorScreenState extends State<SponsorScreen> {
  late Future<SponsorReferrals> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<SponsorRepository>().fetch();
  }

  Future<void> _refresh() async {
    final future = context.read<SponsorRepository>().fetch();
    setState(() => _load = future);
    await future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sponsored users')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your referrals.',
              onRetry: _refresh,
            );
          }

          final referrals = snapshot.data!;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                StatCard(
                  label: 'Sponsored users',
                  value: '${referrals.totals.count}',
                  icon: Icons.groups_outlined,
                ),
                const SizedBox(height: 12),
                StatCard(
                  label: 'Commission available',
                  value: formatPeso(referrals.totals.available),
                  hint: 'In your wallet',
                  icon: Icons.account_balance_wallet_outlined,
                  tone: StatCardTone.accent,
                ),
                const SizedBox(height: 12),
                StatCard(
                  label: 'Commission pending',
                  value: formatPeso(referrals.totals.pending),
                  hint: 'Awaiting Oncall approval',
                  icon: Icons.schedule_outlined,
                ),
                const SizedBox(height: 20),
                if (referrals.page.items.isEmpty)
                  const AppEmptyState(
                    icon: Icons.groups_outlined,
                    title: 'No sponsored users yet',
                    message:
                        'Users who register through your sponsorship will '
                        'appear here. Share your account email with people '
                        'you refer; they enter it as their sponsor when '
                        'signing up.',
                  )
                else
                  for (final sponsored in referrals.page.items) ...[
                    AppCard(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  sponsored.name,
                                  style: const TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 15,
                                    fontWeight: FontWeight.w600,
                                    color: AppColors.ink,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  [
                                    sponsored.accountType,
                                    formatDate(sponsored.joinedAt),
                                  ].whereType<String>().join(' · '),
                                  style: const TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 13,
                                    color: AppColors.inkSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 12),
                          sponsored.commission == null
                              ? const Text(
                                  '—',
                                  style: TextStyle(color: AppColors.inkMuted),
                                )
                              : Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Text(
                                      formatPeso(sponsored.commission!.amount),
                                      style: const TextStyle(
                                        fontFamily: 'Poppins',
                                        fontWeight: FontWeight.w600,
                                        color: AppColors.ink,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    StatusChip(sponsored.commission!.status),
                                  ],
                                ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),
                  ],
              ],
            ),
          );
        },
      ),
    );
  }
}
