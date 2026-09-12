import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/enforcement_repository.dart';
import '../../models/enforcement_case.dart';
import '../../models/paginated.dart';
import '../../widgets/common.dart';

class EnforcementCasesListScreen extends StatefulWidget {
  const EnforcementCasesListScreen({super.key});

  @override
  State<EnforcementCasesListScreen> createState() =>
      _EnforcementCasesListScreenState();
}

class _EnforcementCasesListScreenState
    extends State<EnforcementCasesListScreen> {
  late Future<Paginated<EnforcementCase>> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<EnforcementRepository>().list();
  }

  Future<void> _refresh() async {
    final future = context.read<EnforcementRepository>().list();
    setState(() => _load = future);
    await future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Account notices')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load account notices.',
              onRetry: _refresh,
            );
          }

          final cases = snapshot.data!.items;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: cases.isEmpty
                ? ListView(
                    children: const [
                      SizedBox(height: 120),
                      EmptyView(
                        message:
                            'No account notices. You are in good standing.',
                        icon: Icons.verified_user_outlined,
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: cases.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final c = cases[index];

                      return Card(
                        child: ListTile(
                          title: Text(humanizeStatus(c.violationCategory)),
                          subtitle: Text(formatDate(c.createdAt)),
                          trailing: StatusChip(c.status),
                          onTap: () =>
                              context.push('/enforcement-cases/${c.id}'),
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
