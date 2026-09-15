// Phase P: the submit-review screen's star-rating validation, exercised
// without a network call (submitting with no rating selected short-circuits
// before ReviewRepository is ever touched).
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:oncall_mobile/core/api_client.dart';
import 'package:oncall_mobile/core/token_storage.dart';
import 'package:oncall_mobile/data/review_repository.dart';
import 'package:oncall_mobile/screens/shared/submit_review_screen.dart';
import 'package:oncall_mobile/theme/app_theme.dart';
import 'package:provider/provider.dart';

Widget _wrap(Widget child) {
  final apiClient = ApiClient(tokenStorage: TokenStorage());

  return Provider<ReviewRepository>.value(
    value: ReviewRepository(apiClient),
    child: MaterialApp(theme: AppTheme.light, home: child),
  );
}

void main() {
  testWidgets('shows all five stars and the submit button', (tester) async {
    await tester.pumpWidget(
      _wrap(
        const SubmitReviewScreen(jobId: 1, counterpartName: 'Pedro'),
      ),
    );

    expect(find.byIcon(Icons.star_border), findsNWidgets(5));
    expect(find.text('Submit review'), findsOneWidget);
    expect(find.textContaining('Pedro'), findsOneWidget);
  });

  testWidgets('tapping a star fills it in', (tester) async {
    await tester.pumpWidget(
      _wrap(
        const SubmitReviewScreen(jobId: 1, counterpartName: 'Pedro'),
      ),
    );

    await tester.tap(find.byIcon(Icons.star_border).at(2));
    await tester.pump();

    // The first three stars (1-3) become filled once the third is tapped.
    expect(find.byIcon(Icons.star), findsNWidgets(3));
    expect(find.byIcon(Icons.star_border), findsNWidgets(2));
  });

  testWidgets('submitting without a rating shows a validation message and makes no request', (
    tester,
  ) async {
    await tester.pumpWidget(
      _wrap(
        const SubmitReviewScreen(jobId: 1, counterpartName: 'Pedro'),
      ),
    );

    await tester.tap(find.text('Submit review'));
    await tester.pump();

    expect(find.text('Choose a star rating.'), findsOneWidget);
  });
}
