import 'package:intl/intl.dart';

final _peso = NumberFormat.currency(locale: 'en_PH', symbol: '₱');
final _date = DateFormat('MMM d, yyyy');
final _dateTime = DateFormat('MMM d, h:mm a');

String formatPeso(num? amount) => amount == null ? '—' : _peso.format(amount);

String formatDate(DateTime? date) =>
    date == null ? '—' : _date.format(date.toLocal());

String formatDateTime(DateTime? date) =>
    date == null ? '—' : _dateTime.format(date.toLocal());

/// `SAME_DAY` -> `Same day`, for any enum-shaped status string the API returns.
String humanizeStatus(String? value) {
  if (value == null || value.isEmpty) {
    return '—';
  }

  final words = value.split('_').map((w) => w.toLowerCase()).toList();
  words[0] = words[0][0].toUpperCase() + words[0].substring(1);

  return words.join(' ');
}
