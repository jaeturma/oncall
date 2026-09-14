import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/enforcement_repository.dart';
import '../../models/enforcement_case.dart';
import '../../models/paginated.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_card.dart';
import '../../widgets/app_empty_state.dart';
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
                    padding: const EdgeInsets.all(16),
                    children: const [
                      SizedBox(height: 80),
                      AppEmptyState(
                        icon: Icons.verified_user_outlined,
                        title: 'No enforcement cases',
                        message:
                            'No safety or conduct cases affect your '
                            'account. Keep bookings and communication on '
                            'Oncall to stay in good standing.',
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: cases.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final c = cases[index];

                      return AppCard(
                        onTap: () => context.push('/enforcement-cases/${c.id}'),
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    humanizeStatus(c.violationCategory),
                                    style: const TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 15,
                                      fontWeight: FontWeight.w600,
                                      color: AppColors.ink,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    'Action: ${c.action != null ? humanizeStatus(c.action!) : 'Awaiting admin review'} · '
                                    'opened ${formatDate(c.createdAt)}',
                                    style: const TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 13,
                                      color: AppColors.inkMuted,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 12),
                            StatusChip(c.status),
                          ],
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
