<?php

namespace NostraHealthAI\Modules;

use NostraHealthAI\NostraHealthAI;
use NostraHealthAI\NostraHealthAIException;

/**
 * Eye Diagnosis Analysis Module
 */
class EyeDiagnosisModule
{
    /** @var NostraHealthAI */
    private $client;

    public function __construct(NostraHealthAI $client)
    {
        $this->client = $client;
    }

    /**
     * Analyze an eye image for conditions
     *
     * @param array $params
     *   - file: (required) Path to eye image
     *   - eyeSide: (optional) 'left', 'right', or 'both'
     *   - symptoms: (optional) Array of symptoms
     *   - symptomDuration: (optional) Duration of symptoms
     *   - painLevel: (optional) Pain level 0-10
     *   - visionChanges: (optional) Array of vision changes
     *   - medicalHistory: (optional) Array of medical history
     *   - currentMedications: (optional) Array of medications
     *   - allergies: (optional) Array of allergies
     *   - familyHistory: (optional) Array of family history
     *   - lastEyeExam: (optional) Date of last eye exam
     *   - wearingCorrectiveLenses: (optional) Boolean
     *   - lensType: (optional) Type of lenses
     * @return string Job ID for polling results
     */
    public function analyze(array $params): string
    {
        if (!isset($params['file'])) {
            throw new NostraHealthAIException('File path is required');
        }

        $additionalData = [];
        $fields = [
            'eyeSide', 'symptoms', 'symptomDuration', 'painLevel', 'visionChanges',
            'medicalHistory', 'currentMedications', 'allergies', 'familyHistory',
            'lastEyeExam', 'wearingCorrectiveLenses', 'lensType'
        ];

        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $additionalData[$field] = $params[$field];
            }
        }

        $response = $this->client->uploadFile('/api/v1/ai/eye-diagnosis', $params['file'], 'file', $additionalData);
        return $response['jobId'];
    }

    /**
     * Get the status of an eye diagnosis job
     *
     * @param string $jobId Job ID
     * @return array Job status
     */
    public function getJobStatus(string $jobId): array
    {
        return $this->client->request('GET', "/api/v1/ai/eye-diagnosis/job/{$jobId}");
    }

    /**
     * Wait for an eye diagnosis job to complete
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
                throw new NostraHealthAIException('Eye diagnosis failed: ' . ($status['error'] ?? 'Unknown error'));
            }

            usleep((int) ($pollInterval * 1000000));
            $attempts++;
        }

        throw new NostraHealthAIException('Eye diagnosis timeout');
    }

    /**
     * Get all eye diagnoses for the current user
     *
     * @param int|null $limit Maximum number of results
     * @param string|null $lastDocId Last document ID for pagination
     * @return array List of diagnoses
     */
    public function getAllAnalyses(?int $limit = null, ?string $lastDocId = null): array
    {
        $query = [];
        if ($limit) $query['limit'] = $limit;
        if ($lastDocId) $query['lastDocId'] = $lastDocId;

        $endpoint = '/api/v1/ai/eye-diagnosis';
        if (!empty($query)) {
            $endpoint .= '?' . http_build_query($query);
        }

        return $this->client->request('GET', $endpoint);
    }

    /**
     * Get a specific eye diagnosis by ID
     *
     * @param string $analysisId Analysis ID
     * @return array Diagnosis data
     */
    public function getAnalysis(string $analysisId): array
    {
        $response = $this->client->request('GET', "/api/v1/ai/eye-diagnosis/{$analysisId}");
        return $response['data'];
    }

    /**
     * Delete an eye diagnosis
     *
     * @param string $analysisId Analysis ID
     */
    public function deleteAnalysis(string $analysisId): void
    {
        $this->client->request('DELETE', "/api/v1/ai/eye-diagnosis/{$analysisId}");
    }

    /**
     * Get list of supported eye conditions
     *
     * @return array Supported conditions by category
     */
    public function getSupportedConditions(): array
    {
        $response = $this->client->request('GET', '/api/v1/ai/eye-diagnosis/conditions');
        return $response['data'];
    }
}
