import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/job_repository.dart';
import '../../widgets/common.dart';

const _reportCategories = [
  ('OFF_PLATFORM_CONTACT', 'Asked to pay or contact off-platform'),
  ('HARASSMENT', 'Harassment'),
  ('FRAUD', 'Fraud'),
  ('UNSAFE_BEHAVIOR', 'Unsafe behavior'),
  ('NO_SHOW', 'No show'),
  ('OTHER', 'Other'),
];

/// Incident reporting on the other party in a booking — this is the
/// marketplace safety feature, distinct from staff enforcement review
/// (web-only) and from opening a payment/service dispute.
class ReportUserScreen extends StatefulWidget {
  const ReportUserScreen({super.key, required this.jobId});

  final int jobId;

  @override
  State<ReportUserScreen> createState() => _ReportUserScreenState();
}

class _ReportUserScreenState extends State<ReportUserScreen> {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();
  String _category = _reportCategories.first.$1;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _descriptionController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await context.read<JobRepository>().reportUser(
        widget.jobId,
        category: _category,
        description: _descriptionController.text.trim(),
      );
      if (mounted) {
        showSuccessSnackBar(context, 'Report submitted for admin review.');
        Navigator.of(context).pop();
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
      appBar: AppBar(title: const Text('Report a safety issue')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'No enforcement is automatic — an admin reviews every report.',
              ),
              const SizedBox(height: 16),
              if (_error != null) ...[
                Text(_error!, style: TextStyle(color: Colors.red.shade700)),
                const SizedBox(height: 12),
              ],
              DropdownButtonFormField<String>(
                initialValue: _category,
                decoration: const InputDecoration(
                  labelText: 'What happened?',
                  border: OutlineInputBorder(),
                ),
                items: [
                  for (final (value, label) in _reportCategories)
                    DropdownMenuItem(value: value, child: Text(label)),
                ],
                onChanged: (value) =>
                    setState(() => _category = value ?? _category),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _descriptionController,
                decoration: const InputDecoration(
                  labelText: 'Details',
                  border: OutlineInputBorder(),
                ),
                maxLines: 5,
                maxLength: 3000,
                validator: (value) => (value == null || value.isEmpty)
                    ? 'Please describe what happened'
                    : null,
              ),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: _submitting ? null : _submit,
                child: _submitting
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Submit report'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
