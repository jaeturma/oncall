import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/sponsor_repository.dart';
import '../../widgets/common.dart';

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
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        _totalTile(
                          'Available',
                          formatPeso(referrals.totals.available),
                        ),
                        _totalTile(
                          'Pending',
                          formatPeso(referrals.totals.pending),
                        ),
                        _totalTile('Referrals', '${referrals.totals.count}'),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                if (referrals.page.items.isEmpty)
                  const EmptyView(
                    message:
                        'You have not sponsored anyone yet. Share your account email with people you refer.',
                  )
                else
                  for (final sponsored in referrals.page.items)
                    Card(
                      child: ListTile(
                        title: Text(sponsored.name),
                        subtitle: Text(
                          [
                            sponsored.accountType,
                            formatDate(sponsored.joinedAt),
                          ].whereType<String>().join(' · '),
                        ),
                        trailing: sponsored.commission == null
                            ? const Text('—')
                            : Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    formatPeso(sponsored.commission!.amount),
                                  ),
                                  StatusChip(sponsored.commission!.status),
                                ],
                              ),
                      ),
                    ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _totalTile(String label, String value) => Column(
    children: [
      Text(value, style: Theme.of(context).textTheme.titleMedium),
      Text(label, style: Theme.of(context).textTheme.bodySmall),
    ],
  );
}
