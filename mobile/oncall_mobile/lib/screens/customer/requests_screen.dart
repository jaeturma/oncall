import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/service_request_repository.dart';
import '../../models/paginated.dart';
import '../../models/service_request.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_card.dart';
import '../../widgets/app_empty_state.dart';
import '../../widgets/common.dart';

class RequestsScreen extends StatefulWidget {
  const RequestsScreen({super.key});

  @override
  State<RequestsScreen> createState() => _RequestsScreenState();
}

class _RequestsScreenState extends State<RequestsScreen> {
  late Future<Paginated<ServiceRequest>> _load;

  @override
  void initState() {
    super.initState();
    _load = context.read<ServiceRequestRepository>().list();
  }

  Future<void> _refresh() async {
    final future = context.read<ServiceRequestRepository>().list();
    setState(() => _load = future);
    await future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My requests')),
      body: FutureBuilder(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your requests.',
              onRetry: _refresh,
            );
          }

          final requests = snapshot.data!.items;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: requests.isEmpty
                ? ListView(
                    padding: const EdgeInsets.all(16),
                    children: const [
                      SizedBox(height: 80),
                      AppEmptyState(
                        icon: Icons.inbox_outlined,
                        title: 'No requests yet',
                        message:
                            'When you request a service, it will '
                            'appear here.',
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: requests.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final r = requests[index];

                      return AppCard(
                        onTap: () => context.push(
                          r.jobId != null
                              ? '/jobs/${r.jobId}'
                              : '/service-requests/${r.id}',
                        ),
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    r.title,
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
                                      r.service?.name,
                                      r.requestedProvider?.name,
                                      formatDate(r.createdAt),
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
                            StatusChip(r.status),
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
