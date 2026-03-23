<?php

namespace NostraHealthAI\Modules;

use NostraHealthAI\NostraHealthAI;
use NostraHealthAI\NostraHealthAIException;

/**
 * Drug Verification Module
 */
class DrugVerificationModule
{
    /** @var NostraHealthAI */
    private $client;

    public function __construct(NostraHealthAI $client)
    {
        $this->client = $client;
    }

    /**
     * Verify a drug's authenticity
     *
     * @param array $params
     *   - drugName: (optional) Drug name
     *   - manufacturer: (optional) Manufacturer name
     *   - batchNumber: (optional) Batch/lot number
     *   - ndc: (optional) National Drug Code
     *   - barcode: (optional) Barcode value
     *   - image: (optional) Path to drug package image
     * @return array Verification result
     */
    public function verify(array $params): array
    {
        $additionalData = [];
        $fields = ['drugName', 'manufacturer', 'batchNumber', 'ndc', 'barcode'];

        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $additionalData[$field] = $params[$field];
            }
        }

        if (isset($params['image'])) {
            $response = $this->client->uploadFile('/api/v1/ai/drug-verification/verify', $params['image'], 'image', $additionalData);
        } else {
            // No image, send as JSON
            $multipart = [];
            foreach ($additionalData as $key => $value) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => (string) $value,
                ];
            }

            $response = $this->client->request('POST', '/api/v1/ai/drug-verification/verify', [
                'multipart' => $multipart,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
        }

        return $response['data'];
    }

    /**
     * Verify multiple drugs in batch
     *
     * @param array $drugs Array of drug verification requests
     * @return array Array of verification results
     */
    public function batchVerify(array $drugs): array
    {
        $response = $this->client->request('POST', '/api/v1/ai/drug-verification/verify-batch', [
            'json' => ['drugs' => $drugs],
        ]);
        return $response['data'];
    }

    /**
     * Get all drug verifications for the current user
     *
     * @return array List of verifications
     */
    public function getVerifications(): array
    {
        $response = $this->client->request('GET', '/api/v1/ai/drug-verification/verifications');
        return $response['data'];
    }

    /**
     * Get a specific drug verification by ID
     *
     * @param string $verificationId Verification ID
     * @return array Verification data
     */
    public function getVerification(string $verificationId): array
    {
        $response = $this->client->request('GET', "/api/v1/ai/drug-verification/verifications/{$verificationId}");
        return $response['data'];
    }

    /**
     * Get drug verification statistics
     *
     * @return array Statistics data
     */
    public function getStats(): array
    {
        $response = $this->client->request('GET', '/api/v1/ai/drug-verification/verifications/stats');
        return $response['data'];
    }

    /**
     * Delete a drug verification record
     *
     * @param string $verificationId Verification ID
     */
    public function deleteVerification(string $verificationId): void
    {
        $this->client->request('DELETE', "/api/v1/ai/drug-verification/verifications/{$verificationId}");
    }
}
