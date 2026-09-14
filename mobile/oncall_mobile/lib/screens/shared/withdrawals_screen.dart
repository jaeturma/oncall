import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../core/formatters.dart';
import '../../data/withdrawal_repository.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_card.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/common.dart';
import '../../widgets/withdrawal_status_list.dart';

class WithdrawalsScreen extends StatefulWidget {
  const WithdrawalsScreen({super.key});

  @override
  State<WithdrawalsScreen> createState() => _WithdrawalsScreenState();
}

class _WithdrawalsScreenState extends State<WithdrawalsScreen> {
  late Future<WithdrawalsPage> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<WithdrawalRepository>().list();
  }

  Future<void> _refresh() async {
    final future = context.read<WithdrawalRepository>().list();
    setState(() => _load = future);
    await future;
  }

  Future<void> _cancel(int id) async {
    try {
      await context.read<WithdrawalRepository>().cancel(id);
      await _refresh();
    } on ApiException catch (error) {
      if (mounted) {
        showErrorSnackBar(context, error.message);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Cashouts')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          await context.push('/wallet/withdrawals/new');
          _refresh();
        },
        icon: const Icon(Icons.add),
        label: const Text('New'),
      ),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your cashouts.',
              onRetry: _refresh,
            );
          }

          final withdrawals = snapshot.data!.page.items;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                AppCard(child: const WithdrawalStatusList()),
                const SizedBox(height: 20),
                if (withdrawals.isEmpty)
                  const AppEmptyState(
                    icon: Icons.receipt_long_outlined,
                    title: 'No withdrawal requests yet',
                    message:
                        'Requests you submit will show their progress here.',
                  )
                else
                  for (final w in withdrawals) ...[
                    AppCard(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  formatPeso(w.amount),
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
                                    w.payoutMethod,
                                    formatDate(w.createdAt),
                                    if (w.notes != null) w.notes,
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
                          w.isCancellable
                              ? TextButton(
                                  onPressed: () => _cancel(w.id),
                                  child: const Text('Cancel'),
                                )
                              : StatusChip(w.status),
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
