import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/review_repository.dart';
import '../../theme/app_colors.dart';
import '../../widgets/common.dart';

/// A polished, dedicated review flow (Phase P §49) — replaces the earlier
/// inline dialog on the job screen. Pops `true` on a successful submission
/// so the caller knows to refresh.
class SubmitReviewScreen extends StatefulWidget {
  const SubmitReviewScreen({
    super.key,
    required this.jobId,
    required this.counterpartName,
  });

  final int jobId;
  final String counterpartName;

  @override
  State<SubmitReviewScreen> createState() => _SubmitReviewScreenState();
}

class _SubmitReviewScreenState extends State<SubmitReviewScreen> {
  final _commentController = TextEditingController();
  int _rating = 0;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_rating == 0) {
      showErrorSnackBar(context, 'Choose a star rating.');

      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await context.read<ReviewRepository>().submitReview(
        widget.jobId,
        rating: _rating,
        comment: _commentController.text.trim(),
      );
      if (mounted) {
        showSuccessSnackBar(context, 'Review submitted. Thank you!');
        Navigator.of(context).pop(true);
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Leave a review')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              'How was your service with ${widget.counterpartName}?',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                for (var star = 1; star <= 5; star++)
                  Semantics(
                    label: '$star out of 5 stars',
                    button: true,
                    selected: star <= _rating,
                    child: IconButton(
                      iconSize: 40,
                      onPressed: () => setState(() => _rating = star),
                      icon: Icon(
                        star <= _rating ? Icons.star : Icons.star_border,
                        color: AppColors.gold500,
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              'Tell us about your experience (optional)',
              textAlign: TextAlign.center,
              style: Theme.of(
                context,
              ).textTheme.bodyMedium?.copyWith(color: AppColors.inkMuted),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _commentController,
              maxLines: 4,
              maxLength: 2000,
              decoration: const InputDecoration(
                hintText: 'What went well? What could be better?',
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: const TextStyle(color: AppColors.danger700)),
            ],
            const SizedBox(height: 8),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: _submitting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Submit review'),
            ),
          ],
        ),
      ),
    );
  }
}
