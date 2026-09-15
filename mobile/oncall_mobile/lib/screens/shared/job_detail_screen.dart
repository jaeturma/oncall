import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../core/formatters.dart';
import '../../data/job_repository.dart';
import '../../data/payment_repository.dart';
import '../../data/review_repository.dart';
import '../../models/job.dart';
import '../../models/review_eligibility.dart';
import '../../state/auth_state.dart';
import '../../theme/app_colors.dart';
import '../../widgets/app_card.dart';
import '../../widgets/common.dart';
import '../../widgets/verified_service_badge.dart';

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
  late Future<ReviewEligibility> _eligibility;
  bool _acting = false;

  @override
  void initState() {
    super.initState();
    _load = context.read<JobRepository>().show(widget.jobId);
    _eligibility = context.read<ReviewRepository>().fetchEligibility(
      widget.jobId,
    );
  }

  void _refresh() => setState(() {
    _load = context.read<JobRepository>().show(widget.jobId);
    _eligibility = context.read<ReviewRepository>().fetchEligibility(
      widget.jobId,
    );
  });

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
    final paymentRepository = context.read<PaymentRepository>();
    List<String> methods;
    try {
      methods = await paymentRepository.fetchPaymentMethods();
    } on ApiException catch (error) {
      if (mounted) {
        showErrorSnackBar(context, error.message);
      }
      return;
    }
    if (methods.isEmpty || !mounted) {
      return;
    }

    final referenceController = TextEditingController();
    var selectedMethod = methods.first;
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Confirm payment'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Service amount: ${formatPeso(payment.grossAmount)}'),
              Text('Platform fee: ${formatPeso(payment.platformFee)}'),
              Text(
                'Provider receives: ${formatPeso(payment.netAmount)}',
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: selectedMethod,
                decoration: const InputDecoration(labelText: 'How you paid'),
                items: [
                  for (final method in methods)
                    DropdownMenuItem(
                      value: method,
                      child: Text(humanizeStatus(method)),
                    ),
                ],
                onChanged: (value) {
                  if (value != null) {
                    setDialogState(() => selectedMethod = value);
                  }
                },
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
      ),
    );
    if (result != true) {
      return;
    }

    await _run(() async {
      await paymentRepository.confirmPayment(
        payment.id,
        paymentMethod: selectedMethod,
        paymentReference: referenceController.text.trim(),
      );
    });
  }

  void _openReceipt(JobPayment payment) =>
      context.push('/payments/${payment.id}/receipt');

  Future<void> _requestRefund(JobPayment payment) async {
    final amountController = TextEditingController();
    final reasonController = TextEditingController();
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Request a refund'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Up to ${formatPeso(payment.refundableAmount)} is still refundable.',
            ),
            const SizedBox(height: 12),
            TextField(
              controller: amountController,
              keyboardType: const TextInputType.numberWithOptions(
                decimal: true,
              ),
              decoration: const InputDecoration(labelText: 'Amount'),
            ),
            TextField(
              controller: reasonController,
              decoration: const InputDecoration(labelText: 'Reason'),
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
    );
    if (result != true) {
      return;
    }

    await _run(() async {
      await context.read<PaymentRepository>().requestRefund(
        payment.id,
        amount: amountController.text.trim(),
        reason: reasonController.text.trim(),
      );
      if (mounted) {
        showSuccessSnackBar(context, 'Refund request submitted for review.');
      }
    });
  }

  Future<void> _openSubmitReview(String counterpartName) async {
    final submitted = await context.push<bool>(
      '/jobs/${widget.jobId}/review',
      extra: counterpartName,
    );
    if (submitted == true) {
      _refresh();
    }
  }

  Future<void> _withdrawReview(int reviewId) => _run(
    () => context.read<ReviewRepository>().withdraw(reviewId),
  );

  Future<void> _respondToReview(int reviewId) async {
    final controller = TextEditingController();
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Respond to this review'),
        content: TextField(
          controller: controller,
          maxLength: 1000,
          maxLines: 3,
          decoration: const InputDecoration(hintText: 'Write a public response…'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Post'),
          ),
        ],
      ),
    );
    if (result != true || controller.text.trim().isEmpty) {
      return;
    }

    await _run(
      () => context.read<ReviewRepository>().respond(
        reviewId,
        controller.text.trim(),
      ),
    );
  }

  Future<void> _reportReview(int reviewId) async {
    var category = 'OTHER';
    final descriptionController = TextEditingController();
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Report this review'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<String>(
                initialValue: category,
                items: const [
                  DropdownMenuItem(value: 'HARASSMENT', child: Text('Harassment')),
                  DropdownMenuItem(value: 'SPAM', child: Text('Spam')),
                  DropdownMenuItem(
                    value: 'PERSONAL_INFORMATION',
                    child: Text('Personal information'),
                  ),
                  DropdownMenuItem(
                    value: 'FALSE_OR_MISLEADING',
                    child: Text('False or misleading'),
                  ),
                  DropdownMenuItem(
                    value: 'OFFENSIVE_CONTENT',
                    child: Text('Offensive content'),
                  ),
                  DropdownMenuItem(value: 'THREAT', child: Text('Threat')),
                  DropdownMenuItem(
                    value: 'UNRELATED_CONTENT',
                    child: Text('Unrelated content'),
                  ),
                  DropdownMenuItem(value: 'OTHER', child: Text('Other')),
                ],
                onChanged: (value) =>
                    setDialogState(() => category = value ?? category),
              ),
              TextField(
                controller: descriptionController,
                decoration: const InputDecoration(
                  labelText: 'Details (optional)',
                ),
                maxLines: 2,
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
    if (result != true || !mounted) {
      return;
    }

    setState(() => _acting = true);
    try {
      await context.read<ReviewRepository>().report(
        reviewId,
        category: category,
        description: descriptionController.text.trim(),
      );
      if (mounted) {
        showSuccessSnackBar(context, 'Thanks — we received your report.');
      }
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
                  AppCard(
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
                        Text('Agreed: ${formatPeso(job.payment!.grossAmount)}'),
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
                        if (job.payment!.status == 'PAID' ||
                            job.payment!.status == 'RELEASED') ...[
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              OutlinedButton(
                                onPressed: () => _openReceipt(job.payment!),
                                child: const Text('View receipt'),
                              ),
                              if (!_acting &&
                                  iAmFinder &&
                                  job.payment!.isRefundable)
                                OutlinedButton(
                                  onPressed: () =>
                                      _requestRefund(job.payment!),
                                  child: const Text('Request a refund'),
                                ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 16),
                OutlinedButton.icon(
                  onPressed: () => context.push('/conversations/${job.id}'),
                  icon: const Icon(Icons.chat_bubble_outline),
                  label: Text('Messages (${job.messages.length})'),
                ),

                if (job.status == 'COMPLETED' && !_acting)
                  FutureBuilder<ReviewEligibility>(
                    future: _eligibility,
                    builder: (context, eligibilitySnapshot) {
                      final eligibility = eligibilitySnapshot.data;
                      if (eligibility == null || !eligibility.canReview) {
                        return const SizedBox.shrink();
                      }

                      return Padding(
                        padding: const EdgeInsets.only(top: 12),
                        child: OutlinedButton.icon(
                          onPressed: () => _openSubmitReview(
                            iAmFinder
                                ? job.provider?.name ?? 'the provider'
                                : job.serviceFinder?.name ?? 'the customer',
                          ),
                          icon: const Icon(Icons.star_border),
                          label: const Text('Leave a review'),
                        ),
                      );
                    },
                  ),

                if (job.reviews.any((r) => r.isPublished)) ...[
                  const SizedBox(height: 16),
                  Text(
                    'Reviews',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  for (final review in job.reviews.where((r) => r.isPublished))
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: AppCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  '${review.reviewer?.name ?? '—'} reviewed ${review.reviewee?.name ?? '—'}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                              Row(
                                children: [
                                  for (var star = 1; star <= 5; star++)
                                    Icon(
                                      star <= review.rating
                                          ? Icons.star
                                          : Icons.star_border,
                                      size: 14,
                                      color: AppColors.gold500,
                                    ),
                                ],
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          const VerifiedServiceBadge(),
                          if (review.comment != null) ...[
                            const SizedBox(height: 6),
                            Text(review.comment!),
                          ],
                          if (review.hasResponse) ...[
                            const SizedBox(height: 6),
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: AppColors.surfaceMuted,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Response from provider',
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(review.response!.body),
                                ],
                              ),
                            ),
                          ] else if (!_acting &&
                              me?.id == review.reviewee?.id) ...[
                            const SizedBox(height: 6),
                            TextButton(
                              onPressed: () => _respondToReview(review.id),
                              child: const Text('Respond'),
                            ),
                          ],
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              if (!_acting && me?.id == review.reviewer?.id)
                                TextButton(
                                  onPressed: () => _withdrawReview(review.id),
                                  child: const Text('Withdraw'),
                                )
                              else if (!_acting)
                                TextButton(
                                  onPressed: () => _reportReview(review.id),
                                  child: const Text('Report'),
                                ),
                            ],
                          ),
                        ],
                      ),
                      ),
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
                  AppCard(
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
