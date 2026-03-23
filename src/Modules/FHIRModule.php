<?php

namespace NostraHealthAI\Modules;

use NostraHealthAI\NostraHealthAI;
use NostraHealthAI\NostraHealthAIException;

/**
 * FHIR R4 Module for Healthcare Interoperability
 */
class FHIRModule
{
    /** @var NostraHealthAI */
    private $client;

    public function __construct(NostraHealthAI $client)
    {
        $this->client = $client;
    }

    /**
     * Get comprehensive patient summary (all FHIR resources)
     *
     * @return array Patient summary with all resource types
     */
    public function getPatientSummary(): array
    {
        $response = $this->client->request('GET', '/api/v1/fhir/patient/summary');
        return $response['data'];
    }

    /**
     * Get observations (vital signs, lab results)
     *
     * @param string|null $category Category filter (e.g., 'vital-signs', 'laboratory')
     * @return array List of observation records
     */
    public function getObservations(?string $category = null): array
    {
        $endpoint = '/api/v1/fhir/observations';
        if ($category) {
            $endpoint .= '?category=' . urlencode($category);
        }

        $response = $this->client->request('GET', $endpoint);
        return $response['data'];
    }

    /**
     * Get conditions (diagnoses)
     *
     * @return array List of condition records
     */
    public function getConditions(): array
    {
        $response = $this->client->request('GET', '/api/v1/fhir/conditions');
        return $response['data'];
    }

    /**
     * Get medication statements
     *
     * @return array List of medication records
     */
    public function getMedications(): array
    {
        $response = $this->client->request('GET', '/api/v1/fhir/medications');
        return $response['data'];
    }

    /**
     * Get allergy intolerances
     *
     * @return array List of allergy records
     */
    public function getAllergies(): array
    {
        $response = $this->client->request('GET', '/api/v1/fhir/allergies');
        return $response['data'];
    }

    /**
     * Get document references (prescriptions, medical records)
     *
     * @param string|null $type Document type filter
     * @return array List of document records
     */
    public function getDocuments(?string $type = null): array
    {
        $endpoint = '/api/v1/fhir/documents';
        if ($type) {
            $endpoint .= '?type=' . urlencode($type);
        }

        $response = $this->client->request('GET', $endpoint);
        return $response['data'];
    }

    /**
     * Search FHIR records with advanced filters
     *
     * @param array $params
     *   - resourceType: (optional) FHIR resource type
     *   - category: (optional) Category filter
     *   - status: (optional) 'active' or 'archived'
     *   - startDate: (optional) Start date (ISO format)
     *   - endDate: (optional) End date (ISO format)
     *   - limit: (optional) Maximum results
     * @return array List of matching records
     */
    public function search(array $params): array
    {
        $response = $this->client->request('POST', '/api/v1/fhir/search', [
            'json' => $params,
        ]);
        return $response['data'];
    }

    /**
     * Get FHIR records by resource type
     *
     * @param string $resourceType FHIR resource type (Patient, Observation, etc.)
     * @param int|null $limit Maximum results
     * @return array List of records
     */
    public function getByResourceType(string $resourceType, ?int $limit = null): array
    {
        $endpoint = "/api/v1/fhir/{$resourceType}";
        if ($limit) {
            $endpoint .= '?limit=' . $limit;
        }

        $response = $this->client->request('GET', $endpoint);
        return $response['data'];
    }

    /**
     * Get all FHIR records for the current user
     *
     * @param int|null $limit Maximum results
     * @param string|null $lastDocId Last document ID for pagination
     * @return array Records with pagination info
     */
    public function getAllRecords(?int $limit = null, ?string $lastDocId = null): array
    {
        $query = [];
        if ($limit) $query['limit'] = $limit;
        if ($lastDocId) $query['lastDocId'] = $lastDocId;

        $endpoint = '/api/v1/fhir';
        if (!empty($query)) {
            $endpoint .= '?' . http_build_query($query);
        }

        return $this->client->request('GET', $endpoint);
    }

    /**
     * Get a specific FHIR record by ID
     *
     * @param string $recordId Record ID
     * @return array Record data
     */
    public function getRecord(string $recordId): array
    {
        $response = $this->client->request('GET', "/api/v1/fhir/{$recordId}");
        return $response['data'];
    }

    /**
     * Update a FHIR record
     *
     * @param string $recordId Record ID
     * @param array $resource Updated resource data
     * @return array Updated record
     */
    public function updateRecord(string $recordId, array $resource): array
    {
        $response = $this->client->request('PUT', "/api/v1/fhir/{$recordId}", [
            'json' => ['resource' => $resource],
        ]);
        return $response['data'];
    }

    /**
     * Delete a FHIR record (soft delete)
     *
     * @param string $recordId Record ID
     */
    public function deleteRecord(string $recordId): void
    {
        $this->client->request('DELETE', "/api/v1/fhir/{$recordId}");
    }

    /**
     * Archive a FHIR record
     *
     * @param string $recordId Record ID
     */
    public function archiveRecord(string $recordId): void
    {
        $this->client->request('POST', "/api/v1/fhir/{$recordId}/archive");
    }

    /**
     * Export patient data as FHIR Bundle
     *
     * @param array|null $resourceTypes Resource types to include (null for all)
     * @return array FHIR Bundle
     */
    public function exportBundle(?array $resourceTypes = null): array
    {
        $endpoint = '/api/v1/fhir/export';
        if ($resourceTypes) {
            $endpoint .= '?types=' . implode(',', $resourceTypes);
        }

        $response = $this->client->request('GET', $endpoint);
        return $response['data'];
    }
}
