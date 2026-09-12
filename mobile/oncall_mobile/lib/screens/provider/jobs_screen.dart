import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/job_repository.dart';
import '../../models/job.dart';
import '../../models/paginated.dart';
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
                    children: const [
                      SizedBox(height: 120),
                      EmptyView(
                        message: 'No jobs yet.',
                        icon: Icons.work_off_outlined,
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: jobs.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final job = jobs[index];

                      return Card(
                        child: ListTile(
                          title: Text(job.service?.name ?? 'Job #${job.id}'),
                          subtitle: Text(
                            [
                              job.serviceFinder?.name,
                              formatPeso(job.agreedPrice),
                            ].whereType<String>().join(' · '),
                          ),
                          trailing: StatusChip(job.status),
                          onTap: () => context.push('/jobs/${job.id}'),
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
