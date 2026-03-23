<?php

namespace NostraHealthAI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use NostraHealthAI\Modules\SkinInfectionModule;
use NostraHealthAI\Modules\EyeDiagnosisModule;
use NostraHealthAI\Modules\WoundHealingModule;
use NostraHealthAI\Modules\DrugVerificationModule;
use NostraHealthAI\Modules\FHIRModule;
use NostraHealthAI\Modules\SubscriptionModule;

/**
 * NostraHealthAI SDK Client
 *
 * Official PHP SDK for interacting with NostraHealthAI Medical AI Platform
 *
 * @example
 * ```php
 * $nostra = new NostraHealthAI([
 *     'apiKey' => 'your-api-key',
 *     'baseUrl' => 'https://www.api.nostrahealth.com'
 * ]);
 *
 * // Medical Chat
 * $response = $nostra->chat(['message' => 'What causes high blood pressure?']);
 *
 * // Skin Analysis
 * $jobId = $nostra->skin->analyze(['file' => '/path/to/skin_image.jpg']);
 * $result = $nostra->skin->waitForCompletion($jobId);
 *
 * // FHIR Records
 * $summary = $nostra->fhir->getPatientSummary();
 * ```
 */
class NostraHealthAI
{
    /** @var Client */
    private $client;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $baseUrl;

    /** @var int */
    private $timeout;

    /** @var SkinInfectionModule */
    public $skin;

    /** @var EyeDiagnosisModule */
    public $eye;

    /** @var WoundHealingModule */
    public $wound;

    /** @var DrugVerificationModule */
    public $drug;

    /** @var FHIRModule */
    public $fhir;

    /** @var SubscriptionModule */
    public $subscriptions;

    /**
     * Create a new NostraHealthAI client
     *
     * @param array $config Configuration options
     *   - apiKey: (required) Firebase authentication token
     *   - baseUrl: (optional) API base URL, default: https://www.api.nostrahealth.com
     *   - timeout: (optional) Request timeout in seconds, default: 60
     */
    public function __construct(array $config)
    {
        if (!isset($config['apiKey'])) {
            throw new NostraHealthAIException('API key is required');
        }

        $this->apiKey = $config['apiKey'];
        $this->baseUrl = rtrim($config['baseUrl'] ?? 'https://www.api.nostrahealth.com', '/');
        $this->timeout = $config['timeout'] ?? 60;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);

        // Initialize modules
        $this->skin = new SkinInfectionModule($this);
        $this->eye = new EyeDiagnosisModule($this);
        $this->wound = new WoundHealingModule($this);
        $this->drug = new DrugVerificationModule($this);
        $this->fhir = new FHIRModule($this);
        $this->subscriptions = new SubscriptionModule($this);
    }

    /**
     * Make an HTTP request to the API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $options Request options
     * @return array Response data
     * @throws NostraHealthAIException
     */
    public function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $endpoint, $options);
            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['success']) && $data['success'] === false) {
                throw new NostraHealthAIException(
                    $data['error'] ?? 'Request failed',
                    $response->getStatusCode(),
                    $data
                );
            }

            return $data;
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 0;
            $body = $response ? json_decode($response->getBody()->getContents(), true) : [];

            throw new NostraHealthAIException(
                $body['error'] ?? $e->getMessage(),
                $statusCode,
                $body
            );
        }
    }

    /**
     * Upload a file to the API
     *
     * @param string $endpoint API endpoint
     * @param string $filePath Path to file
     * @param string $fieldName Form field name
     * @param array $additionalData Additional form data
     * @return array Response data
     */
    public function uploadFile(string $endpoint, string $filePath, string $fieldName = 'file', array $additionalData = []): array
    {
        $multipart = [
            [
                'name' => $fieldName,
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ],
        ];

        foreach ($additionalData as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => is_array($value) ? implode(',', $value) : (string) $value,
            ];
        }

        return $this->request('POST', $endpoint, [
            'multipart' => $multipart,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ],
        ]);
    }

    // =========================================================================
    // MEDICAL CHAT
    // =========================================================================

    /**
     * Send a message to the medical AI assistant
     *
     * @param array $params
     *   - message: (required) User message
     *   - conversationId: (optional) Conversation ID to continue
     * @return array Chat response
     */
    public function chat(array $params): array
    {
        $response = $this->request('POST', '/api/v1/ai/chat', [
            'json' => $params,
        ]);

        return $response['data'];
    }

    /**
     * Send an audio message for voice-based conversation
     *
     * @param string $audioFilePath Path to audio file
     * @param string|null $conversationId Optional conversation ID
     * @return array Response with transcription and AI response
     */
    public function audioChat(string $audioFilePath, ?string $conversationId = null): array
    {
        $additionalData = [];
        if ($conversationId) {
            $additionalData['conversationId'] = $conversationId;
        }

        $response = $this->uploadFile('/api/v1/ai/audio-chat', $audioFilePath, 'audio', $additionalData);
        return $response['data'];
    }

    /**
     * Get all conversations for the current user
     *
     * @return array List of conversations
     */
    public function getConversations(): array
    {
        $response = $this->request('GET', '/api/v1/ai/conversations');
        return $response['data'];
    }

    /**
     * Get messages for a specific conversation
     *
     * @param string $conversationId Conversation ID
     * @return array List of messages
     */
    public function getConversationMessages(string $conversationId): array
    {
        $response = $this->request('GET', "/api/v1/ai/conversations/{$conversationId}/messages");
        return $response['data'];
    }

    // =========================================================================
    // MEDICAL FILE ANALYSIS
    // =========================================================================

    /**
     * Analyze a medical file (image, lab report, etc.)
     *
     * @param string $filePath Path to file
     * @return string Job ID for polling results
     */
    public function analyzeFile(string $filePath): string
    {
        $response = $this->uploadFile('/api/v1/ai/analyze', $filePath);
        return $response['jobId'];
    }

    /**
     * Get the status of a file analysis job
     *
     * @param string $jobId Job ID
     * @return array Job status and results
     */
    public function getJobStatus(string $jobId): array
    {
        $response = $this->request('GET', "/api/v1/ai/job/{$jobId}");
        return [
            'jobId' => $response['jobId'],
            'status' => $response['status'],
            'progress' => $response['progress'],
            'metadata' => $response['metadata'],
            'data' => $response['data'] ?? null,
            'error' => $response['error'] ?? null,
        ];
    }

    /**
     * Wait for a file analysis job to complete
     *
     * @param string $jobId Job ID
     * @param float $pollInterval Polling interval in seconds
     * @param int $maxAttempts Maximum polling attempts
     * @return array Completed job status
     * @throws NostraHealthAIException
     */
    public function waitForJobCompletion(string $jobId, float $pollInterval = 2.0, int $maxAttempts = 60): array
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $status = $this->getJobStatus($jobId);

            if ($status['status'] === 'completed') {
                return $status;
            }

            if ($status['status'] === 'failed') {
                throw new NostraHealthAIException(
                    'Job failed: ' . ($status['error'] ?? 'Unknown error')
                );
            }

            usleep((int) ($pollInterval * 1000000));
            $attempts++;
        }

        throw new NostraHealthAIException("Job polling timeout after {$maxAttempts} attempts");
    }

    /**
     * Get all analysis jobs for the current user
     *
     * @return array List of jobs
     */
    public function getUserJobs(): array
    {
        $response = $this->request('GET', '/api/v1/ai/jobs');
        return $response['jobs'];
    }
}
