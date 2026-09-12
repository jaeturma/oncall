import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../core/formatters.dart';
import '../../data/job_repository.dart';
import '../../models/job.dart';
import '../../state/auth_state.dart';
import '../../widgets/common.dart';

const _disputeCategories = [
  ('SERVICE_NOT_AS_AGREED', 'Service not as agreed'),
  ('NO_SHOW', 'No show'),
  ('PAYMENT_ISSUE', 'Payment issue'),
  ('CONDUCT', 'Conduct'),
  ('SAFETY', 'Safety'),
  ('OTHER', 'Other'),
];

class JobDetailScreen extends StatefulWidget {
  const JobDetailScreen({super.key, required this.jobId});

  final int jobId;

  @override
  State<JobDetailScreen> createState() => _JobDetailScreenState();
}

class _JobDetailScreenState extends State<JobDetailScreen> {
  late Future<Job> _load;
  bool _acting = false;

  @override
  void initState() {
    super.initState();
    _load = context.read<JobRepository>().show(widget.jobId);
  }

  void _refresh() =>
      setState(() => _load = context.read<JobRepository>().show(widget.jobId));

  Future<void> _run(Future<void> Function() action) async {
    setState(() => _acting = true);
    try {
      await action();
      _refresh();
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

  Future<void> _transition(String status) => _run(() async {
    await context.read<JobRepository>().updateStatus(
      widget.jobId,
      status: status,
    );
  });

  Future<void> _confirmPayment(JobPayment payment) async {
    final methodController = TextEditingController();
    final referenceController = TextEditingController();
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirm payment'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Confirm you paid the provider ${formatPeso(payment.grossAmount)}.',
            ),
            const SizedBox(height: 12),
            TextField(
              controller: methodController,
              decoration: const InputDecoration(
                labelText: 'How you paid (e.g. GCash)',
              ),
            ),
            TextField(
              controller: referenceController,
              decoration: const InputDecoration(labelText: 'Reference'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Confirm'),
          ),
        ],
      ),
    );
    if (result != true) {
      return;
    }

    await _run(() async {
      await context.read<JobRepository>().confirmPayment(
        payment.id,
        paymentMethod: methodController.text.trim(),
        paymentReference: referenceController.text.trim(),
      );
    });
  }

  Future<void> _submitReview() async {
    var rating = 5;
    final commentController = TextEditingController();
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Leave a review'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  for (var star = 1; star <= 5; star++)
                    IconButton(
                      icon: Icon(
                        star <= rating ? Icons.star : Icons.star_border,
                        color: Colors.amber,
                      ),
                      onPressed: () => setDialogState(() => rating = star),
                    ),
                ],
              ),
              TextField(
                controller: commentController,
                decoration: const InputDecoration(
                  labelText: 'Comment (optional)',
                ),
                maxLines: 3,
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Submit'),
            ),
          ],
        ),
      ),
    );
    if (result != true) {
      return;
    }

    await _run(() async {
      await context.read<JobRepository>().submitReview(
        widget.jobId,
        rating: rating,
        comment: commentController.text.trim(),
      );
    });
  }

  Future<void> _openDispute() async {
    var category = _disputeCategories.first.$1;
    final descriptionController = TextEditingController();
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Report a problem'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<String>(
                initialValue: category,
                items: [
                  for (final (value, label) in _disputeCategories)
                    DropdownMenuItem(value: value, child: Text(label)),
                ],
                onChanged: (value) =>
                    setDialogState(() => category = value ?? category),
              ),
              TextField(
                controller: descriptionController,
                decoration: const InputDecoration(
                  labelText: 'What happened? (min 20 characters)',
                ),
                maxLines: 4,
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Submit'),
            ),
          ],
        ),
      ),
    );
    if (result != true) {
      return;
    }

    await _run(() async {
      await context.read<JobRepository>().openDispute(
        widget.jobId,
        category: category,
        description: descriptionController.text.trim(),
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    final me = context.watch<AuthState>().user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Booking'),
        actions: [
          IconButton(
            icon: const Icon(Icons.report_outlined),
            tooltip: 'Report a safety issue',
            onPressed: () => context.push('/jobs/${widget.jobId}/report'),
          ),
        ],
      ),
      body: FutureBuilder<Job>(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return const ErrorView(message: 'Could not load this booking.');
          }

          final job = snapshot.data!;
          final myId = me?.id;
          final iAmFinder = myId != null && myId == job.serviceFinder?.id;
          final alreadyReviewed = job.reviews.any(
            (r) => r.reviewer?.id == myId,
          );

          return RefreshIndicator(
            onRefresh: () async => _refresh(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        job.service?.name ?? 'Job #${job.id}',
                        style: Theme.of(context).textTheme.headlineSmall,
                      ),
                    ),
                    StatusChip(job.status),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'With ${iAmFinder ? job.provider?.name ?? '—' : job.serviceFinder?.name ?? '—'}',
                ),
                Text(
                  formatPeso(job.agreedPrice),
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 16),

                if (_acting)
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(),
                    ),
                  ),
                if (!_acting && job.allowedTransitions.isNotEmpty)
                  Wrap(
                    spacing: 8,
                    children: [
                      for (final status in job.allowedTransitions)
                        FilledButton(
                          onPressed: () => _transition(status),
                          child: Text(humanizeStatus(status)),
                        ),
                    ],
                  ),

                if (job.payment != null) ...[
                  const SizedBox(height: 16),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Payment',
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                              StatusChip(job.payment!.status),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Agreed: ${formatPeso(job.payment!.grossAmount)}',
                          ),
                          Text(
                            'Platform fee: ${formatPeso(job.payment!.platformFee)}',
                          ),
                          Text(
                            'Provider receives: ${formatPeso(job.payment!.netAmount)}',
                          ),
                          if (!_acting &&
                              iAmFinder &&
                              job.payment!.status == 'PENDING') ...[
                            const SizedBox(height: 12),
                            FilledButton(
                              onPressed: () => _confirmPayment(job.payment!),
                              child: const Text('Confirm payment made'),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ],

                const SizedBox(height: 16),
                OutlinedButton.icon(
                  onPressed: () => context.push('/conversations/${job.id}'),
                  icon: const Icon(Icons.chat_bubble_outline),
                  label: Text('Messages (${job.messages.length})'),
                ),

                if (job.status == 'COMPLETED' &&
                    !alreadyReviewed &&
                    !_acting) ...[
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: _submitReview,
                    icon: const Icon(Icons.star_border),
                    label: const Text('Leave a review'),
                  ),
                ],

                const SizedBox(height: 16),
                if (job.dispute == null && !_acting)
                  TextButton.icon(
                    onPressed: _openDispute,
                    icon: const Icon(Icons.gavel_outlined),
                    label: const Text('Report a problem with this job'),
                  )
                else if (job.dispute != null) ...[
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Dispute',
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                              StatusChip(job.dispute!.status),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text(job.dispute!.description),
                          if (job.dispute!.resolution != null) ...[
                            const SizedBox(height: 8),
                            Text('Resolution: ${job.dispute!.resolution}'),
                          ],
                          if (!_acting && job.dispute!.isOpen) ...[
                            const SizedBox(height: 8),
                            OutlinedButton(
                              onPressed: () => _run(
                                () => context
                                    .read<JobRepository>()
                                    .withdrawDispute(job.dispute!.id),
                              ),
                              child: const Text('Withdraw dispute'),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ],

                const SizedBox(height: 16),
                Text(
                  'Status history',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                for (final log in job.statusLogs)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    dense: true,
                    title: Text(humanizeStatus(log.toStatus)),
                    subtitle: Text(
                      [
                        log.changedBy,
                        formatDateTime(log.createdAt),
                        log.notes,
                      ].whereType<String>().join(' · '),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
