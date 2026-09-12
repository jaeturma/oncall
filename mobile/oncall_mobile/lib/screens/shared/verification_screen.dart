import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../core/formatters.dart';
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
            child: documents.isEmpty
                ? ListView(
                    children: const [
                      SizedBox(height: 120),
                      EmptyView(
                        message:
                            'Submit a government ID to get identity-verified.',
                        icon: Icons.badge_outlined,
                      ),
                    ],
                  )
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: documents.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final doc = documents[index];

                      return Card(
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
                      );
                    },
                  ),
          );
        },
      ),
    );
  }
}
