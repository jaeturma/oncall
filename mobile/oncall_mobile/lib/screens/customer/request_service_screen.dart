import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/provider_search_repository.dart';
import '../../data/service_request_repository.dart';
import '../../models/provider_profile.dart';
import '../../widgets/common.dart';

const _urgencies = [
  ('IMMEDIATE', 'Right now'),
  ('SAME_DAY', 'Same day'),
  ('SCHEDULED', 'Scheduled'),
];

class RequestServiceScreen extends StatefulWidget {
  const RequestServiceScreen({super.key, required this.providerProfileId});

  final int providerProfileId;

  @override
  State<RequestServiceScreen> createState() => _RequestServiceScreenState();
}

class _RequestServiceScreenState extends State<RequestServiceScreen> {
  late Future<ProviderProfile> _load;
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _budgetMinController = TextEditingController();
  final _budgetMaxController = TextEditingController();

  int? _serviceId;
  String _urgency = 'SAME_DAY';
  DateTime? _neededAt;
  bool _safetyAcknowledged = false;
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load = context.read<ProviderSearchRepository>().show(
      widget.providerProfileId,
    );
  }

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _budgetMinController.dispose();
    _budgetMaxController.dispose();
    super.dispose();
  }

  Future<void> _pickNeededAt() async {
    final date = await showDatePicker(
      context: context,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 90)),
    );
    if (date == null || !mounted) {
      return;
    }
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (time == null) {
      return;
    }
    setState(
      () => _neededAt = DateTime(
        date.year,
        date.month,
        date.day,
        time.hour,
        time.minute,
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _serviceId == null) {
      showErrorSnackBar(context, 'Please choose a service.');

      return;
    }
    if (!_safetyAcknowledged) {
      showErrorSnackBar(context, 'Please acknowledge the safety notice.');

      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final serviceRequest = await context
          .read<ServiceRequestRepository>()
          .create(
            providerProfileId: widget.providerProfileId,
            serviceId: _serviceId!,
            provinceId: (await _load).province?.id ?? 0,
            title: _titleController.text.trim(),
            description: _descriptionController.text.trim(),
            urgency: _urgency,
            neededAt: _urgency == 'SCHEDULED' ? _neededAt : null,
            budgetMin: double.tryParse(_budgetMinController.text),
            budgetMax: double.tryParse(_budgetMaxController.text),
          );
      if (!mounted) {
        return;
      }
      showSuccessSnackBar(context, 'Request sent to the provider.');
      context.pushReplacement('/service-requests/${serviceRequest.id}');
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
      appBar: AppBar(title: const Text('Send a service request')),
      body: FutureBuilder<ProviderProfile>(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return const ErrorView(message: 'Could not load this provider.');
          }

          final services = snapshot.data!.services;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (_error != null) ...[
                    Text(_error!, style: TextStyle(color: Colors.red.shade700)),
                    const SizedBox(height: 12),
                  ],
                  DropdownButtonFormField<int>(
                    initialValue: _serviceId,
                    decoration: const InputDecoration(
                      labelText: 'Service',
                      border: OutlineInputBorder(),
                    ),
                    items: services
                        .where((s) => s.service != null)
                        .map(
                          (s) => DropdownMenuItem(
                            value: s.service!.id,
                            child: Text(s.service!.name),
                          ),
                        )
                        .toList(),
                    onChanged: (value) => setState(() => _serviceId = value),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _titleController,
                    decoration: const InputDecoration(
                      labelText: 'Title',
                      border: OutlineInputBorder(),
                      helperText:
                          'No emails, phone numbers, or social handles.',
                    ),
                    maxLength: 160,
                    validator: (value) => (value == null || value.isEmpty)
                        ? 'Title is required'
                        : null,
                  ),
                  TextFormField(
                    controller: _descriptionController,
                    decoration: const InputDecoration(
                      labelText: 'Details (optional)',
                      border: OutlineInputBorder(),
                    ),
                    maxLength: 3000,
                    maxLines: 4,
                  ),
                  const SizedBox(height: 12),
                  SegmentedButton<String>(
                    segments: [
                      for (final (value, label) in _urgencies)
                        ButtonSegment(value: value, label: Text(label)),
                    ],
                    selected: {_urgency},
                    onSelectionChanged: (s) =>
                        setState(() => _urgency = s.first),
                  ),
                  if (_urgency == 'SCHEDULED') ...[
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: _pickNeededAt,
                      icon: const Icon(Icons.schedule),
                      label: Text(
                        _neededAt == null
                            ? 'Choose date & time'
                            : _neededAt.toString(),
                      ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _budgetMinController,
                          decoration: const InputDecoration(
                            labelText: 'Budget min (optional)',
                            border: OutlineInputBorder(),
                          ),
                          keyboardType: TextInputType.number,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextFormField(
                          controller: _budgetMaxController,
                          decoration: const InputDecoration(
                            labelText: 'Budget max (optional)',
                            border: OutlineInputBorder(),
                          ),
                          keyboardType: TextInputType.number,
                        ),
                      ),
                    ],
                  ),
                  CheckboxListTile(
                    contentPadding: EdgeInsets.zero,
                    controlAffinity: ListTileControlAffinity.leading,
                    value: _safetyAcknowledged,
                    onChanged: (value) =>
                        setState(() => _safetyAcknowledged = value ?? false),
                    title: const Text(
                      'I understand Oncall never asks me to pay or share contact details off-platform.',
                    ),
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
                        : const Text('Send request'),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
