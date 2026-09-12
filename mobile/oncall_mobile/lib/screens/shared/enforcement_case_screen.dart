import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_exception.dart';
import '../../core/formatters.dart';
import '../../data/enforcement_repository.dart';
import '../../models/enforcement_case.dart';
import '../../widgets/common.dart';

class EnforcementCaseScreen extends StatefulWidget {
  const EnforcementCaseScreen({super.key, required this.enforcementCaseId});

  final int enforcementCaseId;

  @override
  State<EnforcementCaseScreen> createState() => _EnforcementCaseScreenState();
}

class _EnforcementCaseScreenState extends State<EnforcementCaseScreen> {
  late Future<EnforcementCase> _load;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _load = context.read<EnforcementRepository>().show(
      widget.enforcementCaseId,
    );
  }

  void _refresh() => setState(
    () => _load = context.read<EnforcementRepository>().show(
      widget.enforcementCaseId,
    ),
  );

  Future<void> _appeal() async {
    final enforcementRepository = context.read<EnforcementRepository>();
    final reasonController = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Appeal this case'),
        content: TextField(
          controller: reasonController,
          decoration: const InputDecoration(
            labelText: 'Why should this be reconsidered?',
          ),
          maxLines: 4,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () =>
                Navigator.pop(context, reasonController.text.trim()),
            child: const Text('Submit appeal'),
          ),
        ],
      ),
    );
    if (reason == null || reason.isEmpty) {
      return;
    }

    setState(() => _submitting = true);
    try {
      await enforcementRepository.appeal(
        widget.enforcementCaseId,
        reason: reason,
      );
      if (mounted) {
        showSuccessSnackBar(context, 'Appeal submitted for admin review.');
        _refresh();
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
      appBar: AppBar(title: const Text('Account notice')),
      body: FutureBuilder<EnforcementCase>(
        future: _load,
        builder: (context, snapshot) {
          if (!snapshot.hasData && !snapshot.hasError) {
            return const LoadingView();
          }
          if (snapshot.hasError) {
            return const ErrorView(message: 'Could not load this case.');
          }

          final c = snapshot.data!;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      humanizeStatus(c.violationCategory),
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    StatusChip(c.status),
                  ],
                ),
                const SizedBox(height: 8),
                Text('Severity: ${humanizeStatus(c.severity)}'),
                if (c.action != null)
                  Text('Action: ${humanizeStatus(c.action)}'),
                if (c.startsAt != null)
                  Text(
                    'Effective: ${formatDate(c.startsAt)}${c.endsAt != null ? ' – ${formatDate(c.endsAt)}' : ''}',
                  ),
                if (c.resolution != null) ...[
                  const SizedBox(height: 12),
                  Text(
                    'Resolution',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  Text(c.resolution!),
                ],
                const SizedBox(height: 16),
                Text('Appeal status: ${humanizeStatus(c.appealStatus)}'),
                if (c.appealReason != null)
                  Text('Your appeal: ${c.appealReason}'),
                if (c.canAppeal) ...[
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: _submitting ? null : _appeal,
                    child: const Text('Appeal this case'),
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
