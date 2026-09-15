import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../data/provider_search_repository.dart';
import '../../data/service_request_repository.dart';
import '../../models/provider_profile.dart';
import '../../state/location_state.dart';
import '../../theme/app_colors.dart';
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
  bool _locating = false;
  String? _error;

  // Service-request location is its own snapshot — deliberately never
  // auto-equal to the customer's profile/current location until they
  // explicitly choose it (Phase O §18).
  double? _requestLatitude;
  double? _requestLongitude;
  String? _requestLocationSource;
  String? _requestAreaLabel;

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

  Future<void> _useCurrentLocationForRequest() async {
    setState(() => _locating = true);
    final locationState = context.read<LocationState>();
    final granted = await locationState.useCurrentLocation();
    if (!mounted) {
      return;
    }
    setState(() {
      _locating = false;
      if (granted) {
        _requestLatitude = locationState.latitude;
        _requestLongitude = locationState.longitude;
        _requestLocationSource = 'GPS';
        _requestAreaLabel = locationState.resolvedArea?.label ??
            [
              locationState.resolvedArea?.municipality?.name,
              locationState.resolvedArea?.province?.name,
            ].whereType<String>().join(', ');
      }
    });
    if (!granted && mounted) {
      showErrorSnackBar(
        context,
        'Could not get your current location. Try Choose on map instead.',
      );
    }
  }

  Future<void> _chooseOnMap() async {
    final result = await context.push<Map<String, double>>(
      '/location-picker',
      extra: _requestLatitude != null && _requestLongitude != null
          ? {'latitude': _requestLatitude!, 'longitude': _requestLongitude!}
          : null,
    );
    if (result == null || !mounted) {
      return;
    }
    setState(() {
      _requestLatitude = result['latitude'];
      _requestLongitude = result['longitude'];
      _requestLocationSource = 'MAP_PIN';
      _requestAreaLabel = 'Pinned on map';
    });
  }

  void _clearRequestLocation() {
    setState(() {
      _requestLatitude = null;
      _requestLongitude = null;
      _requestLocationSource = null;
      _requestAreaLabel = null;
    });
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
            latitude: _requestLatitude,
            longitude: _requestLongitude,
            locationSource: _requestLocationSource,
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
                    Text(
                      _error!,
                      style: const TextStyle(color: AppColors.danger700),
                    ),
                    const SizedBox(height: 12),
                  ],
                  DropdownButtonFormField<int>(
                    initialValue: _serviceId,
                    decoration: const InputDecoration(labelText: 'Service'),
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
                    ),
                    maxLength: 3000,
                    maxLines: 4,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'Service location (optional)',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    "Defaults to the provider's general area. Add an exact "
                    'location if it helps — it stays hidden until the '
                    'provider accepts.',
                    style: Theme.of(
                      context,
                    ).textTheme.bodySmall?.copyWith(color: AppColors.inkMuted),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      OutlinedButton.icon(
                        onPressed: _locating
                            ? null
                            : _useCurrentLocationForRequest,
                        icon: _locating
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.my_location),
                        label: const Text('Current location'),
                      ),
                      OutlinedButton.icon(
                        onPressed: _chooseOnMap,
                        icon: const Icon(Icons.map_outlined),
                        label: const Text('Choose on map'),
                      ),
                      if (_requestLatitude != null)
                        OutlinedButton.icon(
                          onPressed: _clearRequestLocation,
                          icon: const Icon(Icons.close),
                          label: const Text('Clear'),
                        ),
                    ],
                  ),
                  if (_requestAreaLabel != null) ...[
                    const SizedBox(height: 6),
                    Text(
                      'Selected: $_requestAreaLabel',
                      style: const TextStyle(color: AppColors.inkSecondary),
                    ),
                  ],
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
