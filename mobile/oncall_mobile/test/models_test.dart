// Model parsing tests. These exist because the Laravel API encodes
// `decimal:2`-cast fields (money, ratings) as JSON *strings* ("300.00"), not
// numbers — a mismatch a naive `as num` cast wouldn't catch until runtime.
// The mobile API's `WalletLedger::availableBalance()` bug the backend test
// suite caught (a fresh User's `status` being null) is exactly this class of
// "only breaks when actually parsed" issue, so these are worth locking in.
import 'package:flutter_test/flutter_test.dart';
import 'package:oncall_mobile/core/json.dart';
import 'package:oncall_mobile/models/job.dart';
import 'package:oncall_mobile/models/paginated.dart';
import 'package:oncall_mobile/models/provider_profile.dart';
import 'package:oncall_mobile/models/user.dart';
import 'package:oncall_mobile/models/wallet.dart';

void main() {
  group('parseNum', () {
    test('parses a decimal-cast string like the API sends', () {
      expect(parseNum('300.00'), 300.0);
    });

    test('parses a plain JSON number', () {
      expect(parseNum(150), 150.0);
    });

    test('returns null for null', () {
      expect(parseNum(null), isNull);
    });
  });

  group('User.fromJson', () {
    test('parses a marketplace role and nested account_type', () {
      final user = User.fromJson({
        'id': 1,
        'name': 'Juan',
        'initials': 'J',
        'email': 'juan@example.com',
        'role': 'SERVICE_FINDER',
        'status': 'ACTIVE',
        'email_verified': true,
        'mobile_verified': false,
        'identity_verified': false,
        'reviews_count': 0,
        'rating': '4.50',
        'account_type': {'id': 2, 'name': 'Verified Provider'},
      });

      expect(user.role, UserRole.serviceFinder);
      expect(user.rating, 4.5);
      expect(user.accountTypeName, 'Verified Provider');
      expect(user.isSuspended, isFalse);
    });

    test(
      'a back-office role never reaches this app, so it parses to a null role rather than guessing',
      () {
        final user = User.fromJson({
          'id': 1,
          'name': 'Admin',
          'initials': 'A',
          'email': 'admin@example.com',
          'role': 'ADMIN',
          'status': 'ACTIVE',
          'email_verified': true,
          'mobile_verified': false,
          'identity_verified': false,
          'reviews_count': 0,
        });

        expect(user.role, isNull);
        expect(user.isProvider, isFalse);
      },
    );
  });

  group('ProviderProfile.fromJson', () {
    test('does not reveal a name when the API omitted it', () {
      final profile = ProviderProfile.fromJson({
        'id': 1,
        'availability_status': 'AVAILABLE',
        'available_now': true,
        'verification_status': 'VERIFIED',
        'completed_jobs': 3,
        'verified_document_types': ['NATIONAL_ID'],
        'services': [],
      });

      expect(profile.name, isNull);
      expect(profile.displayName, 'Verified provider');
    });

    test('uses the revealed name when present', () {
      final profile = ProviderProfile.fromJson({
        'id': 1,
        'name': 'Maria Santos',
        'availability_status': 'BUSY',
        'available_now': false,
        'verification_status': 'VERIFIED',
        'completed_jobs': 10,
        'verified_document_types': [],
        'services': [],
      });

      expect(profile.displayName, 'Maria Santos');
    });
  });

  group('Job.fromJson', () {
    test('parses allowed_transitions and nested payment/dispute', () {
      final job = Job.fromJson({
        'id': 5,
        'status': 'ACCEPTED',
        'agreed_price': '800.00',
        'allowed_transitions': ['ON_THE_WAY', 'CANCELLED'],
        'status_logs': [],
        'messages': [],
        'reviews': [],
        'payment': null,
        'dispute': null,
      });

      expect(job.agreedPrice, 800.0);
      expect(job.allowedTransitions, ['ON_THE_WAY', 'CANCELLED']);
      expect(job.isActive, isTrue);
    });
  });

  group('Withdrawal', () {
    test(
      'is only cancellable during accounting review, matching WithdrawalPolicy::cancel',
      () {
        final withdrawal = Withdrawal.fromJson({
          'id': 1,
          'amount': '200.00',
          'status': 'ACCOUNTING_REVIEW',
          'payout_method': 'GCash',
          'payout_reference': '0917',
        });
        final disbursed = Withdrawal.fromJson({
          'id': 2,
          'amount': '200.00',
          'status': 'COMPLETED',
          'payout_method': 'GCash',
          'payout_reference': '0917',
        });

        expect(withdrawal.isCancellable, isTrue);
        expect(disbursed.isCancellable, isFalse);
      },
    );
  });

  group('Paginated.fromJson', () {
    test('reads data and meta', () {
      final page = Paginated.fromJson({
        'data': [
          {'id': 1, 'name': 'A'},
          {'id': 2, 'name': 'B'},
        ],
        'meta': {'current_page': 1, 'last_page': 3, 'total': 30},
      }, (json) => json['name'] as String);

      expect(page.items, ['A', 'B']);
      expect(page.hasMore, isTrue);
      expect(page.total, 30);
    });
  });
}
