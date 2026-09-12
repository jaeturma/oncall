import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../data/service_request_repository.dart';
import '../../models/paginated.dart';
import '../../models/service_request.dart';
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
                    children: const [
                      SizedBox(height: 120),
                      EmptyView(
                        message:
                            'No service requests yet. Search for a provider to get started.',
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: requests.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final r = requests[index];

                      return Card(
                        child: ListTile(
                          title: Text(r.title),
                          subtitle: Text(
                            [
                              r.service?.name,
                              r.requestedProvider?.name,
                              formatDate(r.createdAt),
                            ].whereType<String>().join(' · '),
                          ),
                          trailing: StatusChip(r.status),
                          onTap: () => context.push(
                            r.jobId != null
                                ? '/jobs/${r.jobId}'
                                : '/service-requests/${r.id}',
                          ),
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
