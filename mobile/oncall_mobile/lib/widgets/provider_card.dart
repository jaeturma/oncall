import 'package:flutter/material.dart';

import '../models/provider_profile.dart';
import '../theme/app_colors.dart';
import 'app_badge.dart';
import 'app_card.dart';
import 'availability_badge.dart';
import 'avatar.dart';
import 'rating_summary.dart';
import 'verification_badge.dart';

/// Mirrors Laravel's `resources/views/components/provider-card.blade.php` —
/// mobile-adapted per the design-system alignment plan: the whole card taps
/// through to the profile (Laravel's separate "View profile" button is
/// redundant on a single-tap mobile list), and the footer keeps only the
/// primary "Request service" action, or a lock notice when the provider's
/// identity hasn't been revealed to this viewer (`profile.name == null`).
/// Distance is only ever shown when the backend actually returned one —
/// never fabricated.
class ProviderCard extends StatelessWidget {
  const ProviderCard({
    super.key,
    required this.profile,
    required this.onTap,
    this.onRequestService,
  });

  final ProviderProfile profile;
  final VoidCallback onTap;
  final VoidCallback? onRequestService;

  static const _maxOtherServices = 3;

  @override
  Widget build(BuildContext context) {
    final revealed = profile.name != null;
    final services = profile.services;
    final primaryService = services.isNotEmpty
        ? services.first.service?.name
        : null;
    final otherServices = services.length > 1 ? services.sublist(1) : const [];
    final location = [
      profile.municipality?.name,
      profile.province?.name,
    ].whereType<String>().join(', ');
    final badges = _verificationBadges();

    return AppCard(
      onTap: onTap,
      padding: EdgeInsets.zero,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Avatar(
                  name: profile.name,
                  anonymous: !revealed,
                  size: AvatarSize.lg,
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      AvailabilityBadge(status: profile.availabilityStatus),
                      const SizedBox(height: 8),
                      Text(
                        profile.displayName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 17,
                          fontWeight: FontWeight.w600,
                          color: AppColors.ink,
                        ),
                      ),
                      if (primaryService != null) ...[
                        const SizedBox(height: 2),
                        Text(
                          primaryService,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                            color: AppColors.inkSecondary,
                          ),
                        ),
                      ],
                      if (location.isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(
                              Icons.place_outlined,
                              size: 15,
                              color: AppColors.inkMuted,
                            ),
                            const SizedBox(width: 4),
                            Expanded(
                              child: Text.rich(
                                TextSpan(
                                  text: location,
                                  style: const TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 13,
                                    color: AppColors.inkMuted,
                                  ),
                                  children: profile.distanceKm != null
                                      ? [
                                          TextSpan(
                                            text:
                                                ' · ~${profile.distanceKm!.toStringAsFixed(1)} km away (approximate)',
                                          ),
                                        ]
                                      : null,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          RatingSummary(rating: profile.rating),
                          const SizedBox(width: 12),
                          Text(
                            '${profile.completedJobs} completed',
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 13,
                              color: AppColors.inkSecondary,
                            ),
                          ),
                        ],
                      ),
                      if (badges.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 6,
                          runSpacing: 6,
                          children: badges
                              .map((type) => VerificationBadge(type: type))
                              .toList(),
                        ),
                      ],
                      if (otherServices.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 6,
                          runSpacing: 6,
                          children: [
                            for (final offering in otherServices.take(
                              _maxOtherServices,
                            ))
                              if (offering.service?.name != null)
                                AppBadge(
                                  label: offering.service!.name,
                                  tone: AppBadgeTone.outline,
                                ),
                            if (otherServices.length > _maxOtherServices)
                              AppBadge(
                                label:
                                    '+${otherServices.length - _maxOtherServices} more',
                                tone: AppBadgeTone.outline,
                              ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
          DecoratedBox(
            decoration: const BoxDecoration(
              color: AppColors.surfaceMuted,
              border: Border(top: BorderSide(color: AppColors.line)),
            ),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              child: revealed
                  ? SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        onPressed: onRequestService ?? onTap,
                        child: const Text('Request service'),
                      ),
                    )
                  : const Row(
                      children: [
                        Icon(
                          Icons.lock_outline,
                          size: 14,
                          color: AppColors.inkMuted,
                        ),
                        SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Name and contact details are hidden until a booking is confirmed.',
                            style: TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 12,
                              color: AppColors.inkMuted,
                            ),
                          ),
                        ),
                      ],
                    ),
            ),
          ),
        ],
      ),
    );
  }

  List<String> _verificationBadges() {
    final badges = <String>[
      for (final docType in profile.verifiedDocumentTypes)
        if (VerificationBadge.typeForDocumentType(docType) case final mapped?)
          mapped,
    ];
    if (profile.mobileVerified == true) {
      badges.add('mobile');
    }

    return badges;
  }
}
