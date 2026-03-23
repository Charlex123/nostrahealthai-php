<?php

namespace NostraHealthAI\Modules;

use NostraHealthAI\NostraHealthAI;
use NostraHealthAI\NostraHealthAIException;

/**
 * Skin Infection Analysis Module
 */
class SkinInfectionModule
{
    /** @var NostraHealthAI */
    private $client;

    public function __construct(NostraHealthAI $client)
    {
        $this->client = $client;
    }

    /**
     * Analyze a skin image for infections and conditions
     *
     * @param array $params
     *   - file: (required) Path to skin image
     *   - affectedArea: (optional) Body area affected
     *   - duration: (optional) How long symptoms have persisted
     *   - symptoms: (optional) Array of symptoms
     *   - previousTreatments: (optional) Array of previous treatments
     *   - allergies: (optional) Array of allergies
     *   - medicalHistory: (optional) Array of medical history items
     * @return string Job ID for polling results
     */
    public function analyze(array $params): string
    {
        if (!isset($params['file'])) {
            throw new NostraHealthAIException('File path is required');
        }

        $additionalData = [];
        if (isset($params['affectedArea'])) $additionalData['affectedArea'] = $params['affectedArea'];
        if (isset($params['duration'])) $additionalData['duration'] = $params['duration'];
        if (isset($params['symptoms'])) $additionalData['symptoms'] = $params['symptoms'];
        if (isset($params['previousTreatments'])) $additionalData['previousTreatments'] = $params['previousTreatments'];
        if (isset($params['allergies'])) $additionalData['allergies'] = $params['allergies'];
        if (isset($params['medicalHistory'])) $additionalData['medicalHistory'] = $params['medicalHistory'];

        $response = $this->client->uploadFile('/api/v1/ai/skin-infections', $params['file'], 'file', $additionalData);
        return $response['jobId'];
    }

    /**
     * Get the status of a skin analysis job
     *
     * @param string $jobId Job ID
     * @return array Job status
     */
    public function getJobStatus(string $jobId): array
    {
        return $this->client->request('GET', "/api/v1/ai/skin-infections/job/{$jobId}");
    }

    /**
     * Wait for a skin analysis job to complete
     *
     * @param string $jobId Job ID
     * @param float $pollInterval Polling interval in seconds
     * @param int $maxAttempts Maximum attempts
     * @return array Completed job status
     */
    public function waitForCompletion(string $jobId, float $pollInterval = 2.0, int $maxAttempts = 60): array
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $status = $this->getJobStatus($jobId);

            if ($status['status'] === 'completed') {
                return $status;
            }

            if ($status['status'] === 'failed') {
                throw new NostraHealthAIException('Skin analysis failed: ' . ($status['error'] ?? 'Unknown error'));
            }

            usleep((int) ($pollInterval * 1000000));
            $attempts++;
        }

        throw new NostraHealthAIException('Skin analysis timeout');
    }

    /**
     * Get all skin analyses for the current user
     *
     * @return array List of analyses
     */
    public function getAllAnalyses(): array
    {
        $response = $this->client->request('GET', '/api/v1/ai/skin-infections');
        return $response['data'];
    }

    /**
     * Get a specific skin analysis by ID
     *
     * @param string $analysisId Analysis ID
     * @return array Analysis data
     */
    public function getAnalysis(string $analysisId): array
    {
        $response = $this->client->request('GET', "/api/v1/ai/skin-infections/{$analysisId}");
        return $response['data'];
    }

    /**
     * Delete a skin analysis
     *
     * @param string $analysisId Analysis ID
     */
    public function deleteAnalysis(string $analysisId): void
    {
        $this->client->request('DELETE', "/api/v1/ai/skin-infections/{$analysisId}");
    }

    /**
     * Get list of supported skin conditions
     *
     * @return array Supported conditions by category
     */
    public function getSupportedConditions(): array
    {
        $response = $this->client->request('GET', '/api/v1/ai/skin-infections/conditions');
        return $response['data'];
    }
}
