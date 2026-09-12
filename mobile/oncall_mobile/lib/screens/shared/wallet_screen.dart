import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/wallet_repository.dart';
import '../../widgets/common.dart';

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
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Available balance'),
                        Text(
                          formatPeso(overview.availableBalance),
                          style: Theme.of(context).textTheme.headlineMedium,
                        ),
                        if (overview.pendingBalance > 0) ...[
                          const SizedBox(height: 8),
                          Text(
                            'Pending: ${formatPeso(overview.pendingBalance)}',
                          ),
                        ],
                        const SizedBox(height: 16),
                        FilledButton.icon(
                          onPressed: () =>
                              context.push('/wallet/withdrawals/new'),
                          icon: const Icon(Icons.arrow_downward),
                          label: const Text('Cash out'),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Recent activity',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                if (overview.page.items.isEmpty)
                  const Padding(
                    padding: EdgeInsets.only(top: 12),
                    child: EmptyView(message: 'No wallet activity yet.'),
                  ),
                for (final transaction in overview.page.items)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: Icon(
                      transaction.isCredit
                          ? Icons.add_circle_outline
                          : Icons.remove_circle_outline,
                      color: transaction.isCredit ? Colors.green : Colors.red,
                    ),
                    title: Text(humanizeStatus(transaction.type)),
                    subtitle: Text(
                      [
                        transaction.description,
                        formatDateTime(transaction.createdAt),
                      ].whereType<String>().join(' · '),
                    ),
                    trailing: Text(formatPeso(transaction.amount)),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
