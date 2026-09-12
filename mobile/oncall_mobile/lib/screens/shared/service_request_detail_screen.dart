import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../core/formatters.dart';
import '../../data/service_request_repository.dart';
import '../../models/service_request.dart';
import '../../models/user.dart';
import '../../state/auth_state.dart';
import '../../widgets/common.dart';

class ServiceRequestDetailScreen extends StatefulWidget {
  const ServiceRequestDetailScreen({super.key, required this.serviceRequestId});

  final int serviceRequestId;

  @override
  State<ServiceRequestDetailScreen> createState() =>
      _ServiceRequestDetailScreenState();
}

class _ServiceRequestDetailScreenState
    extends State<ServiceRequestDetailScreen> {
  late Future<ServiceRequest> _load;
  bool _acting = false;

  @override
  void initState() {
    super.initState();
    _load = context.read<ServiceRequestRepository>().show(
      widget.serviceRequestId,
    );
  }

  Future<void> _accept(ServiceRequest request) async {
    final priceController = TextEditingController(
      text: request.budgetMax?.toStringAsFixed(0) ?? '',
    );
    final agreedPrice = await showDialog<double>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Accept request'),
        content: TextField(
          controller: priceController,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(labelText: 'Agreed price (₱)'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () =>
                Navigator.pop(context, double.tryParse(priceController.text)),
            child: const Text('Accept'),
          ),
        ],
      ),
    );
    if (agreedPrice == null) {
      return;
    }

    await _run(() async {
      final jobId = await context.read<ServiceRequestRepository>().accept(
        request.id,
        agreedPrice: agreedPrice,
      );
      if (mounted) {
        context.pushReplacement('/jobs/$jobId');
      }
    });
  }

  Future<void> _decline(ServiceRequest request) => _run(() async {
    await context.read<ServiceRequestRepository>().decline(request.id);
    if (mounted) {
      showSuccessSnackBar(context, 'Request declined.');
      setState(
        () => _load = context.read<ServiceRequestRepository>().show(
          widget.serviceRequestId,
        ),
      );
    }
  });

  Future<void> _cancel(ServiceRequest request) => _run(() async {
    await context.read<ServiceRequestRepository>().cancel(request.id);
    if (mounted) {
      showSuccessSnackBar(context, 'Request cancelled.');
      setState(
        () => _load = context.read<ServiceRequestRepository>().show(
          widget.serviceRequestId,
        ),
      );
    }
  });

  Future<void> _run(Future<void> Function() action) async {
    setState(() => _acting = true);
    try {
      await action();
    } on ApiException catch (error) {
      if (mounted) {
        showErrorSnackBar(context, error.message);
      }
    } finally {
      if (mounted) {
        setState(() => _acting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final me = context.watch<AuthState>().user;

    return Scaffold(
      appBar: AppBar(title: const Text('Service request')),
      body: FutureBuilder<ServiceRequest>(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return const ErrorView(message: 'Could not load this request.');
          }

          final request = snapshot.data!;
          final isTargetedProvider = me?.role == UserRole.serviceProvider;
          final isFinder = me?.role == UserRole.serviceFinder;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        request.title,
                        style: Theme.of(context).textTheme.headlineSmall,
                      ),
                    ),
                    StatusChip(request.status),
                  ],
                ),
                const SizedBox(height: 8),
                if (request.description != null) Text(request.description!),
                const SizedBox(height: 16),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.build_outlined),
                  title: Text(request.service?.name ?? '—'),
                ),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.place_outlined),
                  title: Text(
                    [
                      request.municipality?.name,
                      request.province?.name,
                    ].whereType<String>().join(', '),
                  ),
                ),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.priority_high),
                  title: Text(humanizeStatus(request.urgency)),
                ),
                if (request.neededAt != null)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.schedule),
                    title: Text(formatDateTime(request.neededAt)),
                  ),
                if (request.budgetMin != null || request.budgetMax != null)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.payments_outlined),
                    title: Text(
                      '${formatPeso(request.budgetMin)} – ${formatPeso(request.budgetMax)}',
                    ),
                  ),
                const SizedBox(height: 24),
                if (_acting) const Center(child: CircularProgressIndicator()),
                if (!_acting &&
                    isTargetedProvider &&
                    request.status == 'REQUESTED') ...[
                  FilledButton(
                    onPressed: () => _accept(request),
                    child: const Text('Accept'),
                  ),
                  const SizedBox(height: 8),
                  OutlinedButton(
                    onPressed: () => _decline(request),
                    child: const Text('Decline'),
                  ),
                ],
                if (!_acting && isFinder && request.isOpen)
                  OutlinedButton(
                    onPressed: () => _cancel(request),
                    child: const Text('Cancel request'),
                  ),
                if (request.jobId != null) ...[
                  const SizedBox(height: 12),
                  FilledButton.tonal(
                    onPressed: () => context.push('/jobs/${request.jobId}'),
                    child: const Text('View booking'),
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
