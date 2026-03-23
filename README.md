# NostraHealthAI PHP SDK

Official PHP SDK for the NostraHealthAI Medical AI Platform.

## Requirements

- PHP 7.4 or higher
- Composer
- ext-json

## Installation

```bash
composer require nostrahealthai/sdk
```

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use NostraHealthAI\NostraHealthAI;

$nostra = new NostraHealthAI([
    'apiKey' => 'your-api-key',
    'baseUrl' => 'https://www.api.nostrahealth.com', // optional
    'timeout' => 60 // optional, in seconds
]);

// Medical Chat
$response = $nostra->chat(['message' => 'What are the symptoms of diabetes?']);
echo $response['response'];
```

## Features

### Medical Chat

```php
// Text chat
$response = $nostra->chat(['message' => 'What causes high blood pressure?']);
echo $response['response'];
echo $response['conversationId']; // Save for follow-up

// Continue conversation
$followUp = $nostra->chat([
    'message' => 'What are the treatments?',
    'conversationId' => $response['conversationId']
]);

// Audio chat
$response = $nostra->audioChat('./question.mp3');
echo $response['transcription'];
echo $response['response'];

// Get conversation history
$conversations = $nostra->getConversations();
$messages = $nostra->getConversationMessages($conversationId);
```

### Skin Infection Analysis

```php
// Analyze skin image
$jobId = $nostra->skin->analyze([
    'file' => './skin_image.jpg',
    'affectedArea' => 'forearm',
    'duration' => '3 days',
    'symptoms' => ['itching', 'redness', 'swelling'],
    'previousTreatments' => ['hydrocortisone'],
    'allergies' => ['penicillin'],
    'medicalHistory' => ['eczema']
]);

// Wait for results
$result = $nostra->skin->waitForCompletion($jobId);
print_r($result['data']['primaryDiagnosis']);
print_r($result['data']['recommendations']);

// Get supported conditions
$conditions = $nostra->skin->getSupportedConditions();

// History
$analyses = $nostra->skin->getAllAnalyses();
$analysis = $nostra->skin->getAnalysis($analysisId);
$nostra->skin->deleteAnalysis($analysisId);
```

### Eye Diagnosis

```php
// Analyze eye image
$jobId = $nostra->eye->analyze([
    'file' => './eye_image.jpg',
    'eyeSide' => 'right', // 'left', 'right', or 'both'
    'symptoms' => ['redness', 'irritation', 'discharge'],
    'symptomDuration' => '2 days',
    'painLevel' => 5, // 0-10
    'visionChanges' => ['blurry vision'],
    'medicalHistory' => ['glaucoma family history'],
    'currentMedications' => ['eye drops'],
    'allergies' => ['pollen'],
    'wearingCorrectiveLenses' => true,
    'lensType' => 'contact lenses'
]);

// Wait for results
$result = $nostra->eye->waitForCompletion($jobId);
print_r($result['data']['primaryDiagnosis']);
print_r($result['data']['urgencyLevel']);

// Get supported conditions
$conditions = $nostra->eye->getSupportedConditions();
```

### Wound Healing Tracker

```php
// Create wound profile for tracking
$profile = $nostra->wound->createProfile([
    'name' => 'Post-surgery Wound',
    'woundType' => 'surgical',
    'bodyLocation' => 'abdomen',
    'etiology' => 'appendectomy',
    'notes' => 'Healing well so far'
]);

// Analyze wound
$jobId = $nostra->wound->analyze([
    'file' => './wound_image.jpg',
    'woundProfileId' => $profile['id'],
    'painLevel' => 3,
    'symptoms' => ['slight redness'],
    'currentTreatments' => ['antibiotic ointment']
]);

$result = $nostra->wound->waitForCompletion($jobId);
print_r($result['data']['healingProgress']); // 'improving', 'stable', 'declining'
print_r($result['data']['infectionRisk']); // 'low', 'medium', 'high'

// Track healing timeline
$timeline = $nostra->wound->getTimeline($profile['id']);
print_r($timeline['overallTrend']);

// Manage profiles
$profiles = $nostra->wound->getProfiles();
$nostra->wound->updateProfile($profileId, ['notes' => 'Updated notes']);
$nostra->wound->archiveProfile($profileId); // Mark as healed
$nostra->wound->deleteProfile($profileId);

// Get reference data
$referenceData = $nostra->wound->getReferenceData();
print_r($referenceData['woundTypes']);
print_r($referenceData['bodyLocations']);
```

### Drug Verification

```php
// Verify single drug
$result = $nostra->drug->verify([
    'drugName' => 'Lipitor',
    'manufacturer' => 'Pfizer',
    'batchNumber' => 'XY123456',
    'ndc' => '0071-0155-23',
    'image' => './drug_package.jpg' // optional
]);

echo $result['verificationStatus']; // 'verified', 'unverified', 'suspicious', 'counterfeit'
print_r($result['drugInfo']);
print_r($result['warnings']);

// Batch verification
$results = $nostra->drug->batchVerify([
    ['drugName' => 'Aspirin', 'batchNumber' => 'A123'],
    ['drugName' => 'Ibuprofen', 'batchNumber' => 'B456']
]);

// Statistics
$stats = $nostra->drug->getStats();
echo "Total verifications: " . $stats['totalVerifications'];
echo "Verified: " . $stats['verified'];
echo "Suspicious: " . $stats['suspicious'];

// History
$verifications = $nostra->drug->getVerifications();
$verification = $nostra->drug->getVerification($verificationId);
$nostra->drug->deleteVerification($verificationId);
```

### FHIR R4 (Healthcare Interoperability)

```php
// Get comprehensive patient summary
$summary = $nostra->fhir->getPatientSummary();
print_r($summary['patient']);
print_r($summary['observations']);
print_r($summary['conditions']);
print_r($summary['medications']);
print_r($summary['allergies']);

// Get specific resources
$vitalSigns = $nostra->fhir->getObservations('vital-signs');
$labResults = $nostra->fhir->getObservations('laboratory');
$conditions = $nostra->fhir->getConditions();
$medications = $nostra->fhir->getMedications();
$allergies = $nostra->fhir->getAllergies();
$documents = $nostra->fhir->getDocuments();

// Advanced search
$results = $nostra->fhir->search([
    'resourceType' => 'Observation',
    'category' => 'vital-signs',
    'startDate' => '2024-01-01',
    'limit' => 100
]);

// Get by resource type
$patients = $nostra->fhir->getByResourceType('Patient');
$observations = $nostra->fhir->getByResourceType('Observation', 50);

// Export for hospital integration
$bundle = $nostra->fhir->exportBundle([
    'Patient',
    'Observation',
    'Condition',
    'MedicationStatement'
]);

// Use with Epic, Cerner, Allscripts, etc.
// Send $bundle to hospital FHIR server
```

## Hospital Integration Example

```php
<?php
use NostraHealthAI\NostraHealthAI;
use GuzzleHttp\Client;

$nostra = new NostraHealthAI(['apiKey' => 'your-api-key']);

// Get patient data in FHIR format
$bundle = $nostra->fhir->exportBundle();

// Send to Epic FHIR Server
$epicClient = new Client([
    'base_uri' => 'https://epic-fhir-server.hospital.com',
    'headers' => [
        'Authorization' => 'Bearer ' . $epicAccessToken,
        'Content-Type' => 'application/fhir+json'
    ]
]);

$response = $epicClient->post('/api/FHIR/R4', [
    'json' => $bundle
]);

echo "Data sent to Epic: " . $response->getStatusCode();
```

## Error Handling

```php
<?php
use NostraHealthAI\NostraHealthAI;
use NostraHealthAI\NostraHealthAIException;

try {
    $result = $nostra->skin->analyze(['file' => './image.jpg']);
} catch (NostraHealthAIException $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo 'Status Code: ' . $e->getStatusCode() . PHP_EOL;

    $response = $e->getResponse();
    if ($response) {
        print_r($response);
    }

    // Handle specific errors
    switch ($e->getStatusCode()) {
        case 401:
            echo 'Invalid API key';
            break;
        case 429:
            echo 'Rate limited. Please retry later.';
            break;
        case 400:
            echo 'Bad request: ' . $e->getMessage();
            break;
    }
}
```

## Rate Limits

| Endpoint | Limit |
|----------|-------|
| Chat | 60 requests/minute |
| File Analysis | 20 requests/minute |
| FHIR | 100 requests/minute |

## Supported File Types

| Type | Formats |
|------|---------|
| Images | PNG, JPEG, JPG, WEBP, GIF |
| Documents | PDF |
| Audio | MP3, WAV, WEBM, M4A |

Max file size: **10MB** (25MB for audio)

## License

MIT License

## Support

- Documentation: https://docs.nostrahealthai.com
- Issues: https://github.com/nostrahealthai/sdk/issues
- Email: support@nostrahealth.com
