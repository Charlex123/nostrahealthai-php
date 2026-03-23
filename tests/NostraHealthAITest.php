<?php

/**
 * Unit tests for NostraHealthAI PHP SDK.
 * Tests SDK construction, module initialization, and error handling.
 *
 * Run with: php tests/NostraHealthAITest.php
 */

// ============================================================================
// Mock GuzzleHttp classes before autoloading
// ============================================================================

// Create mock GuzzleHttp classes if not available
if (!class_exists('GuzzleHttp\Client')) {
    // Define the namespace classes we need
    eval('
    namespace GuzzleHttp {
        class Client {
            public function __construct(array $config = []) {}
            public function request(string $method, string $uri, array $options = []) {
                return new \GuzzleHttp\Psr7\Response();
            }
        }
    }
    namespace GuzzleHttp\Exception {
        class RequestException extends \RuntimeException {
            public function getResponse() { return null; }
        }
    }
    namespace GuzzleHttp\Psr7 {
        class Response {
            public function getStatusCode() { return 200; }
            public function getBody() { return new Stream(); }
        }
        class Stream {
            public function getContents() { return "{}"; }
        }
    }
    ');
}

require_once __DIR__ . '/../src/NostraHealthAIException.php';
require_once __DIR__ . '/../src/Modules/SkinInfectionModule.php';
require_once __DIR__ . '/../src/Modules/EyeDiagnosisModule.php';
require_once __DIR__ . '/../src/Modules/WoundHealingModule.php';
require_once __DIR__ . '/../src/Modules/DrugVerificationModule.php';
require_once __DIR__ . '/../src/Modules/FHIRModule.php';
require_once __DIR__ . '/../src/Modules/SubscriptionModule.php';
require_once __DIR__ . '/../src/NostraHealthAI.php';

$passed = 0;
$failed = 0;

function assert_true(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  ✓ {$message}\n";
        $passed++;
    } else {
        echo "  ✗ {$message}\n";
        $failed++;
    }
}

function describe(string $name, callable $fn): void
{
    echo "\n{$name}\n";
    $fn();
}

// ============================================================================
// TESTS
// ============================================================================

describe('Client Initialization', function () {
    // Test requires API key
    try {
        new \NostraHealthAI\NostraHealthAI([]);
        assert_true(false, 'Throws when API key is missing');
    } catch (\NostraHealthAI\NostraHealthAIException $e) {
        assert_true(true, 'Throws when API key is missing');
    }

    // Test creates client with API key
    $client = new \NostraHealthAI\NostraHealthAI(['apiKey' => 'test-api-key']);
    assert_true($client !== null, 'Creates client with valid API key');

    // Test creates client with custom base URL
    $client = new \NostraHealthAI\NostraHealthAI([
        'apiKey' => 'test-api-key',
        'baseUrl' => 'https://custom.api.com'
    ]);
    assert_true($client !== null, 'Creates client with custom base URL');

    // Test creates client with custom timeout
    $client = new \NostraHealthAI\NostraHealthAI([
        'apiKey' => 'test-api-key',
        'timeout' => 30
    ]);
    assert_true($client !== null, 'Creates client with custom timeout');
});

describe('Module Initialization', function () {
    $client = new \NostraHealthAI\NostraHealthAI(['apiKey' => 'test-api-key']);

    assert_true($client->skin !== null, 'Skin module is initialized');
    assert_true($client->eye !== null, 'Eye module is initialized');
    assert_true($client->wound !== null, 'Wound module is initialized');
    assert_true($client->drug !== null, 'Drug module is initialized');
    assert_true($client->fhir !== null, 'FHIR module is initialized');
    assert_true($client->subscriptions !== null, 'Subscription module is initialized');

    assert_true($client->skin instanceof \NostraHealthAI\Modules\SkinInfectionModule, 'Skin is SkinInfectionModule');
    assert_true($client->eye instanceof \NostraHealthAI\Modules\EyeDiagnosisModule, 'Eye is EyeDiagnosisModule');
    assert_true($client->wound instanceof \NostraHealthAI\Modules\WoundHealingModule, 'Wound is WoundHealingModule');
    assert_true($client->drug instanceof \NostraHealthAI\Modules\DrugVerificationModule, 'Drug is DrugVerificationModule');
    assert_true($client->fhir instanceof \NostraHealthAI\Modules\FHIRModule, 'FHIR is FHIRModule');
    assert_true($client->subscriptions instanceof \NostraHealthAI\Modules\SubscriptionModule, 'Subscriptions is SubscriptionModule');
});

describe('Module Methods Exist', function () {
    $client = new \NostraHealthAI\NostraHealthAI(['apiKey' => 'test-api-key']);

    // Skin module
    assert_true(method_exists($client->skin, 'analyze'), 'skin->analyze exists');
    assert_true(method_exists($client->skin, 'waitForCompletion'), 'skin->waitForCompletion exists');
    assert_true(method_exists($client->skin, 'getAllAnalyses'), 'skin->getAllAnalyses exists');
    assert_true(method_exists($client->skin, 'getAnalysis'), 'skin->getAnalysis exists');
    assert_true(method_exists($client->skin, 'deleteAnalysis'), 'skin->deleteAnalysis exists');
    assert_true(method_exists($client->skin, 'getSupportedConditions'), 'skin->getSupportedConditions exists');

    // Eye module
    assert_true(method_exists($client->eye, 'analyze'), 'eye->analyze exists');
    assert_true(method_exists($client->eye, 'waitForCompletion'), 'eye->waitForCompletion exists');
    assert_true(method_exists($client->eye, 'getSupportedConditions'), 'eye->getSupportedConditions exists');

    // Wound module
    assert_true(method_exists($client->wound, 'analyze'), 'wound->analyze exists');
    assert_true(method_exists($client->wound, 'createProfile'), 'wound->createProfile exists');
    assert_true(method_exists($client->wound, 'getTimeline'), 'wound->getTimeline exists');

    // Drug module
    assert_true(method_exists($client->drug, 'verify'), 'drug->verify exists');
    assert_true(method_exists($client->drug, 'batchVerify'), 'drug->batchVerify exists');
    assert_true(method_exists($client->drug, 'getStats'), 'drug->getStats exists');

    // FHIR module
    assert_true(method_exists($client->fhir, 'getPatientSummary'), 'fhir->getPatientSummary exists');
    assert_true(method_exists($client->fhir, 'getObservations'), 'fhir->getObservations exists');
    assert_true(method_exists($client->fhir, 'exportBundle'), 'fhir->exportBundle exists');

    // Subscription module
    assert_true(method_exists($client->subscriptions, 'getPlans'), 'subscriptions->getPlans exists');
    assert_true(method_exists($client->subscriptions, 'getPlanDetails'), 'subscriptions->getPlanDetails exists');
    assert_true(method_exists($client->subscriptions, 'getMySubscription'), 'subscriptions->getMySubscription exists');
    assert_true(method_exists($client->subscriptions, 'purchase'), 'subscriptions->purchase exists');
    assert_true(method_exists($client->subscriptions, 'change'), 'subscriptions->change exists');
    assert_true(method_exists($client->subscriptions, 'cancel'), 'subscriptions->cancel exists');
    assert_true(method_exists($client->subscriptions, 'getAiUsage'), 'subscriptions->getAiUsage exists');
});

describe('Exception Handling', function () {
    $ex = new \NostraHealthAI\NostraHealthAIException('Test error', 400, ['detail' => 'bad']);
    assert_true($ex->getMessage() === 'Test error', 'Exception has correct message');
    assert_true($ex->getStatusCode() === 400, 'Exception has correct status code');
    assert_true($ex->getResponse()['detail'] === 'bad', 'Exception has correct response');

    $ex2 = new \NostraHealthAI\NostraHealthAIException('Network error');
    assert_true($ex2->getStatusCode() === null, 'Exception without status code is null');
    assert_true($ex2->getResponse() === null, 'Exception without response is null');
});

describe('Top-level Methods', function () {
    $client = new \NostraHealthAI\NostraHealthAI(['apiKey' => 'test-api-key']);

    assert_true(method_exists($client, 'chat'), 'chat method exists');
    assert_true(method_exists($client, 'audioChat'), 'audioChat method exists');
    assert_true(method_exists($client, 'getConversations'), 'getConversations method exists');
    assert_true(method_exists($client, 'getConversationMessages'), 'getConversationMessages method exists');
    assert_true(method_exists($client, 'analyzeFile'), 'analyzeFile method exists');
    assert_true(method_exists($client, 'getJobStatus'), 'getJobStatus method exists');
    assert_true(method_exists($client, 'waitForJobCompletion'), 'waitForJobCompletion method exists');
    assert_true(method_exists($client, 'getUserJobs'), 'getUserJobs method exists');
});

// ============================================================================
// SUMMARY
// ============================================================================

echo "\n" . str_repeat('=', 50) . "\n";
echo "Results: {$passed} passed, {$failed} failed, " . ($passed + $failed) . " total\n";
if ($failed > 0) {
    exit(1);
} else {
    echo "All tests passed!\n";
}
