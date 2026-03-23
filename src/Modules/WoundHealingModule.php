<?php

namespace NostraHealthAI\Modules;

use NostraHealthAI\NostraHealthAI;
use NostraHealthAI\NostraHealthAIException;

/**
 * Wound Healing Analysis and Tracking Module
 */
class WoundHealingModule
{
    /** @var NostraHealthAI */
    private $client;

    public function __construct(NostraHealthAI $client)
    {
        $this->client = $client;
    }

    /**
     * Analyze a wound image
     *
     * @param array $params
     *   - file: (required) Path to wound image
     *   - woundProfileId: (optional) ID of wound profile for tracking
     *   - woundType: (optional) Type of wound
     *   - bodyLocation: (optional) Body location
     *   - painLevel: (optional) Pain level 0-10
     *   - symptoms: (optional) Array of symptoms
     *   - currentTreatments: (optional) Array of current treatments
     *   - recentChanges: (optional) Description of recent changes
     * @return string Job ID for polling results
     */
    public function analyze(array $params): string
    {
        if (!isset($params['file'])) {
            throw new NostraHealthAIException('File path is required');
        }

        $additionalData = [];
        $fields = ['woundProfileId', 'woundType', 'bodyLocation', 'painLevel', 'symptoms', 'currentTreatments', 'recentChanges'];

        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $additionalData[$field] = $params[$field];
            }
        }

        $response = $this->client->uploadFile('/api/v1/ai/wound-healing/analyze', $params['file'], 'file', $additionalData);
        return $response['jobId'];
    }

    /**
     * Get the status of a wound analysis job
     *
     * @param string $jobId Job ID
     * @return array Job status
     */
    public function getJobStatus(string $jobId): array
    {
        return $this->client->request('GET', "/api/v1/ai/wound-healing/job/{$jobId}");
    }

    /**
     * Wait for a wound analysis job to complete
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
                throw new NostraHealthAIException('Wound analysis failed: ' . ($status['error'] ?? 'Unknown error'));
            }

            usleep((int) ($pollInterval * 1000000));
            $attempts++;
        }

        throw new NostraHealthAIException('Wound analysis timeout');
    }

    /**
     * Get all wound analyses for the current user
     *
     * @param int|null $limit Maximum number of results
     * @return array List of analyses
     */
    public function getAllAnalyses(?int $limit = null): array
    {
        $endpoint = '/api/v1/ai/wound-healing/analyses';
        if ($limit) {
            $endpoint .= '?limit=' . $limit;
        }

        $response = $this->client->request('GET', $endpoint);
        return $response['data'];
    }

    /**
     * Get a specific wound analysis by ID
     *
     * @param string $analysisId Analysis ID
     * @return array Analysis data
     */
    public function getAnalysis(string $analysisId): array
    {
        $response = $this->client->request('GET', "/api/v1/ai/wound-healing/analyses/{$analysisId}");
        return $response['data'];
    }

    /**
     * Delete a wound analysis
     *
     * @param string $analysisId Analysis ID
     */
    public function deleteAnalysis(string $analysisId): void
    {
        $this->client->request('DELETE', "/api/v1/ai/wound-healing/analyses/{$analysisId}");
    }

    // =========================================================================
    // WOUND PROFILES
    // =========================================================================

    /**
     * Create a new wound profile for tracking
     *
     * @param array $params
     *   - name: (required) Profile name
     *   - woundType: (required) Type of wound
     *   - bodyLocation: (required) Body location
     *   - bodyLocationDetails: (optional) Additional location details
     *   - etiology: (optional) Cause of wound
     *   - woundOnsetDate: (optional) When wound started
     *   - notes: (optional) Additional notes
     * @return array Created profile
     */
    public function createProfile(array $params): array
    {
        $response = $this->client->request('POST', '/api/v1/ai/wound-healing/profiles', [
            'json' => $params,
        ]);
        return $response['data'];
    }

    /**
     * Get all wound profiles for the current user
     *
     * @return array Profiles grouped by status (active, archived)
     */
    public function getProfiles(): array
    {
        $response = $this->client->request('GET', '/api/v1/ai/wound-healing/profiles');
        return $response['data'];
    }

    /**
     * Get a specific wound profile with recent analyses
     *
     * @param string $profileId Profile ID
     * @return array Profile data with analyses
     */
    public function getProfile(string $profileId): array
    {
        $response = $this->client->request('GET', "/api/v1/ai/wound-healing/profiles/{$profileId}");
        return $response['data'];
    }

    /**
     * Update a wound profile
     *
     * @param string $profileId Profile ID
     * @param array $updates Fields to update
     * @return array Updated profile
     */
    public function updateProfile(string $profileId, array $updates): array
    {
        $response = $this->client->request('PUT', "/api/v1/ai/wound-healing/profiles/{$profileId}", [
            'json' => $updates,
        ]);
        return $response['data'];
    }

    /**
     * Archive a wound profile (mark as healed)
     *
     * @param string $profileId Profile ID
     */
    public function archiveProfile(string $profileId): void
    {
        $this->client->request('POST', "/api/v1/ai/wound-healing/profiles/{$profileId}/archive");
    }

    /**
     * Delete a wound profile and all associated analyses
     *
     * @param string $profileId Profile ID
     */
    public function deleteProfile(string $profileId): void
    {
        $this->client->request('DELETE', "/api/v1/ai/wound-healing/profiles/{$profileId}");
    }

    /**
     * Get wound healing timeline for a profile
     *
     * @param string $profileId Profile ID
     * @return array|null Timeline data or null if insufficient data
     */
    public function getTimeline(string $profileId): ?array
    {
        $response = $this->client->request('GET', "/api/v1/ai/wound-healing/profiles/{$profileId}/timeline");
        return $response['data'];
    }

    /**
     * Get reference data (wound types and body locations)
     *
     * @return array Reference data
     */
    public function getReferenceData(): array
    {
        $response = $this->client->request('GET', '/api/v1/ai/wound-healing/reference-data');
        return $response['data'];
    }
}
