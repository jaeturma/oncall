import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../core/formatters.dart';
import '../../data/withdrawal_repository.dart';
import '../../widgets/common.dart';

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
            child: withdrawals.isEmpty
                ? ListView(
                    children: const [
                      SizedBox(height: 120),
                      EmptyView(
                        message: 'No cashout requests yet.',
                        icon: Icons.receipt_long_outlined,
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: withdrawals.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final w = withdrawals[index];

                      return Card(
                        child: ListTile(
                          title: Text(formatPeso(w.amount)),
                          subtitle: Text(
                            [
                              w.payoutMethod,
                              formatDate(w.createdAt),
                              if (w.notes != null) w.notes,
                            ].whereType<String>().join(' · '),
                          ),
                          trailing: w.isCancellable
                              ? TextButton(
                                  onPressed: () => _cancel(w.id),
                                  child: const Text('Cancel'),
                                )
                              : StatusChip(w.status),
                        ),
                      );
                    },
                  ),
          );
        },
      ),
    );
  }
}
