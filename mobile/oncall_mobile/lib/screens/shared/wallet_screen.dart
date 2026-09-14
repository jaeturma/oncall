import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/wallet_repository.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_card.dart';
import '../../widgets/common.dart';
import '../../widgets/stat_card.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  late Future<WalletOverview> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<WalletRepository>().fetch();
  }

  Future<void> _refresh() async {
    final future = context.read<WalletRepository>().fetch();
    setState(() => _load = future);
    await future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Wallet'),
        actions: [
          IconButton(
            icon: const Icon(Icons.receipt_long_outlined),
            tooltip: 'Cashouts',
            onPressed: () => context.push('/wallet/withdrawals'),
          ),
        ],
      ),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your wallet.',
              onRetry: _refresh,
            );
          }

          final overview = snapshot.data!;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Expanded(
                      child: StatCard(
                        label: 'Available balance',
                        value: formatPeso(overview.availableBalance),
                        hint: 'Ready to withdraw',
                        icon: Icons.account_balance_wallet_outlined,
                        tone: StatCardTone.accent,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: StatCard(
                        label: 'Pending',
                        value: formatPeso(overview.pendingBalance),
                        hint: 'Not yet withdrawable',
                        icon: Icons.schedule_outlined,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () => context.push('/wallet/withdrawals/new'),
                    icon: const Icon(Icons.arrow_downward),
                    label: const Text('Cash out'),
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  'Recent activity',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 8),
                if (overview.page.items.isEmpty)
                  const Padding(
                    padding: EdgeInsets.only(top: 12),
                    child: EmptyView(message: 'No wallet activity yet.'),
                  )
                else
                  AppCard(
                    padding: EdgeInsets.zero,
                    child: Column(
                      children: [
                        for (final transaction in overview.page.items) ...[
                          ListTile(
                            leading: Icon(
                              transaction.isCredit
                                  ? Icons.add_circle_outline
                                  : Icons.remove_circle_outline,
                              color: transaction.isCredit
                                  ? AppColors.success700
                                  : AppColors.danger700,
                            ),
                            title: Text(humanizeStatus(transaction.type)),
                            subtitle: Text(
                              [
                                transaction.description,
                                formatDateTime(transaction.createdAt),
                              ].whereType<String>().join(' · '),
                            ),
                            trailing: Text(
                              formatPeso(transaction.amount),
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontWeight: FontWeight.w600,
                                color: transaction.isCredit
                                    ? AppColors.success700
                                    : AppColors.danger700,
                              ),
                            ),
                          ),
                          if (transaction != overview.page.items.last)
                            const Divider(height: 1, color: AppColors.line),
                        ],
                      ],
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
