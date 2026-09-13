import 'dart:async';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../core/formatters.dart';
import '../../data/mobile_verification_repository.dart';
import '../../data/verification_repository.dart';
import '../../models/verification.dart';
import '../../state/auth_state.dart';
import '../../widgets/common.dart';

const _documentTypes = [
  ('NATIONAL_ID', 'National ID'),
  ('DRIVERS_LICENSE', "Driver's license"),
  ('PASSPORT', 'Passport'),
  ('PROFESSIONAL_CREDENTIAL', 'Professional credential'),
];

class VerificationScreen extends StatefulWidget {
  const VerificationScreen({super.key});

  @override
  State<VerificationScreen> createState() => _VerificationScreenState();
}

class _VerificationScreenState extends State<VerificationScreen> {
  late Future<List<ProviderDocument>> _load;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _load = context.read<VerificationRepository>().fetch();
  }

  Future<void> _refresh() async {
    final future = context.read<VerificationRepository>().fetch();
    setState(() => _load = future);
    await future;
  }

  Future<void> _submit() async {
    var documentType = _documentTypes.first.$1;
    final pickedType = await showDialog<String>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Document type'),
          content: DropdownButtonFormField<String>(
            initialValue: documentType,
            items: [
              for (final (value, label) in _documentTypes)
                DropdownMenuItem(value: value, child: Text(label)),
            ],
            onChanged: (value) =>
                setDialogState(() => documentType = value ?? documentType),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, documentType),
              child: const Text('Next: choose photo'),
            ),
          ],
        ),
      ),
    );
    if (pickedType == null || !mounted) {
      return;
    }

    final image = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
    );
    if (image == null || !mounted) {
      return;
    }

    setState(() => _submitting = true);
    try {
      await context.read<VerificationRepository>().submit(
        documentType: pickedType,
        filePath: image.path,
      );
      if (mounted) {
        showSuccessSnackBar(context, 'Document submitted for review.');
        await context.read<AuthState>().refreshUser();
        await _refresh();
      }
    } on ApiException catch (error) {
      if (mounted) {
        showErrorSnackBar(context, error.message);
      }
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Verification')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _submitting ? null : _submit,
        icon: const Icon(Icons.upload_file_outlined),
        label: const Text('Submit document'),
      ),
      body: FutureBuilder<List<ProviderDocument>>(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: 'Could not load your documents.',
              onRetry: _refresh,
            );
          }

          final documents = snapshot.data!;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                const _MobileVerificationCard(),
                const SizedBox(height: 16),
                Text(
                  'Identity documents',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: 8),
                if (documents.isEmpty)
                  const EmptyView(
                    message: 'Submit a government ID to get identity-verified.',
                    icon: Icons.badge_outlined,
                  )
                else
                  for (final doc in documents) ...[
                    Card(
                      child: ListTile(
                        title: Text(humanizeStatus(doc.documentType)),
                        subtitle: Text(
                          [
                            formatDate(doc.createdAt),
                            if (doc.notes != null) doc.notes,
                          ].whereType<String>().join(' · '),
                        ),
                        trailing: StatusChip(doc.status),
                      ),
                    ),
                    const SizedBox(height: 8),
                  ],
              ],
            ),
          );
        },
      ),
    );
  }
}

/// Mobile-number OTP verification card. Every decision (whether a code is
/// valid, expired, rate-limited, or correct) is made by Laravel — this
/// widget only ever renders the state the API returns; it never generates
/// or checks an OTP itself.
class _MobileVerificationCard extends StatefulWidget {
  const _MobileVerificationCard();

  @override
  State<_MobileVerificationCard> createState() =>
      _MobileVerificationCardState();
}

class _MobileVerificationCardState extends State<_MobileVerificationCard> {
  bool _busy = false;
  Timer? _cooldownTimer;
  int _cooldownRemaining = 0;

  @override
  void dispose() {
    _cooldownTimer?.cancel();
    super.dispose();
  }

  void _startCooldown(int seconds) {
    _cooldownTimer?.cancel();
    setState(() => _cooldownRemaining = seconds);
    _cooldownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();

        return;
      }
      setState(
        () => _cooldownRemaining = (_cooldownRemaining - 1).clamp(0, seconds),
      );
      if (_cooldownRemaining == 0) {
        timer.cancel();
      }
    });
  }

  Future<void> _startVerification() async {
    final phoneController = TextEditingController(
      text: context.read<AuthState>().user?.phone ?? '',
    );
    final phone = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Verify your mobile number'),
        content: TextField(
          controller: phoneController,
          keyboardType: TextInputType.phone,
          decoration: const InputDecoration(
            labelText: 'Mobile number',
            hintText: '09171234567',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () =>
                Navigator.pop(context, phoneController.text.trim()),
            child: const Text('Send code'),
          ),
        ],
      ),
    );
    if (phone == null || phone.isEmpty || !mounted) {
      return;
    }

    await _request(
      () => context.read<MobileVerificationRepository>().request(phone),
    );
  }

  Future<void> _resend() async {
    await _request(() => context.read<MobileVerificationRepository>().resend());
  }

  Future<void> _request(Future<OtpRequestResult> Function() action) async {
    setState(() => _busy = true);
    try {
      final result = await action();
      _startCooldown(result.resendAvailableInSeconds);
      if (mounted) {
        final message = result.demoCode != null
            ? 'Code sent. Demo code (no SMS provider configured): ${result.demoCode}.'
            : 'We sent a code to ${result.mobile}.';
        showSuccessSnackBar(context, message);
        await _promptForCode();
      }
    } on ApiException catch (error) {
      if (mounted) {
        if (error.retryAfterSeconds != null) {
          _startCooldown(error.retryAfterSeconds!);
        }
        showErrorSnackBar(context, error.message);
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  Future<void> _promptForCode() async {
    final codeController = TextEditingController();
    final code = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Enter verification code'),
        content: TextField(
          controller: codeController,
          keyboardType: TextInputType.number,
          maxLength: 8,
          decoration: const InputDecoration(labelText: 'Code'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, codeController.text.trim()),
            child: const Text('Verify'),
          ),
        ],
      ),
    );
    if (code == null || code.isEmpty || !mounted) {
      return;
    }

    setState(() => _busy = true);
    try {
      await context.read<MobileVerificationRepository>().verify(code);
      if (mounted) {
        showSuccessSnackBar(context, 'Your mobile number is verified.');
        await context.read<AuthState>().refreshUser();
      }
    } on ApiException catch (error) {
      // Deliberately generic on the backend ("Invalid or expired verification
      // code.") regardless of whether it was wrong, expired, or already used
      // — rendered as-is, never guessed at here.
      if (mounted) {
        showErrorSnackBar(context, error.message);
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthState>().user;
    final verified = user?.mobileVerified ?? false;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Mobile number',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                if (verified)
                  const Chip(
                    avatar: Icon(Icons.check, size: 16),
                    label: Text('Verified'),
                  ),
              ],
            ),
            const SizedBox(height: 4),
            Text(user?.phone ?? 'No mobile number on file'),
            const SizedBox(height: 12),
            if (_busy)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(8),
                  child: CircularProgressIndicator(),
                ),
              )
            else if (_cooldownRemaining > 0)
              OutlinedButton(
                onPressed: null,
                child: Text('Resend available in ${_cooldownRemaining}s'),
              )
            else if (verified)
              OutlinedButton(
                onPressed: _startVerification,
                child: const Text('Change mobile number'),
              )
            else ...[
              FilledButton(
                onPressed: _startVerification,
                child: const Text('Verify mobile number'),
              ),
              if (user?.phone != null) ...[
                const SizedBox(height: 8),
                TextButton(
                  onPressed: _resend,
                  child: const Text('Resend code'),
                ),
              ],
            ],
          ],
        ),
      ),
    );
  }
}
