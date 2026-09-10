<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use App\Models\ProviderDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderDocument>
 */
class ProviderDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'document_type' => DocumentType::NationalId,
            'private_path' => 'verification-documents/example.pdf',
            'status' => VerificationStatus::Submitted,
        ];
    }
}
