import '../core/api_client.dart';
import '../models/verification.dart';

class VerificationRepository {
  VerificationRepository(this._client);

  final ApiClient _client;

  Future<List<ProviderDocument>> fetch() async {
    final json = await _client.get('/verification');

    return (json['data'] as List<dynamic>)
        .map((e) => ProviderDocument.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<ProviderDocument> submit({
    required String documentType,
    required String filePath,
  }) async {
    final json = await _client.uploadFile(
      '/verification/documents',
      fieldName: 'document',
      filePath: filePath,
      fields: {'document_type': documentType},
    );

    return ProviderDocument.fromJson(json['data'] as Map<String, dynamic>);
  }
}
