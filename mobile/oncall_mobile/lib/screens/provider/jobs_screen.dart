import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/job_repository.dart';
import '../../models/job.dart';
import '../../models/paginated.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_card.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/common.dart';

class JobsScreen extends StatefulWidget {
  const JobsScreen({super.key});

  @override
  State<JobsScreen> createState() => _JobsScreenState();
}

class _JobsScreenState extends State<JobsScreen> {
  late Future<Paginated<Job>> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<JobRepository>().list();
  }

  Future<void> _refresh() async {
    final future = context.read<JobRepository>().list();
    setState(() => _load = future);
    await future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My jobs')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your jobs.',
              onRetry: _refresh,
            );
          }

          final jobs = snapshot.data!.items;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: jobs.isEmpty
                ? ListView(
                    padding: const EdgeInsets.all(16),
                    children: const [
                      SizedBox(height: 80),
                      AppEmptyState(
                        icon: Icons.work_outline,
                        title: 'No confirmed bookings yet',
                        message:
                            'Accepted service requests become bookings and '
                            'show up here.',
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: jobs.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final job = jobs[index];

                      return AppCard(
                        onTap: () => context.push('/jobs/${job.id}'),
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    job.service?.name ?? 'Job #${job.id}',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
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
                                      job.serviceFinder?.name,
                                      formatPeso(job.agreedPrice),
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
                            StatusChip(job.status),
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
