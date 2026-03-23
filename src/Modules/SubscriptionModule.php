<?php

namespace NostraHealthAI\Modules;

use NostraHealthAI\NostraHealthAI;
use NostraHealthAI\NostraHealthAIException;

/**
 * AI Subscription Management Module
 *
 * Manage subscription plans, purchases, and usage tracking.
 */
class SubscriptionModule
{
    /** @var NostraHealthAI */
    private $client;

    public function __construct(NostraHealthAI $client)
    {
        $this->client = $client;
    }

    /**
     * Get all available subscription plans
     *
     * @return array Plans and category limits per tier
     */
    public function getPlans(): array
    {
        return $this->client->request('GET', '/api/v1/ai/subscriptions/plans');
    }

    /**
     * Get details for a specific subscription plan
     *
     * @param string $tier Plan tier (free, basic, standard, premium, premium_plus)
     * @return array Plan details
     */
    public function getPlanDetails(string $tier): array
    {
        $response = $this->client->request('GET', "/api/v1/ai/subscriptions/plans/{$tier}");
        return $response['plan'];
    }

    /**
     * Get current user's subscription
     *
     * @return array Subscription details
     */
    public function getMySubscription(): array
    {
        $response = $this->client->request('GET', '/api/v1/ai/subscriptions/my-subscription');
        return $response['subscription'];
    }

    /**
     * Purchase a subscription
     *
     * @param array $params
     *   - tier: (required) Subscription tier
     *   - paymentMethodId: (required for paid tiers) Payment method ID
     *   - billingCycle: (optional) 'monthly' or 'yearly'
     * @return array Subscription, transaction, and message
     */
    public function purchase(array $params): array
    {
        return $this->client->request('POST', '/api/v1/ai/subscriptions/purchase', [
            'json' => $params,
        ]);
    }

    /**
     * Change (upgrade or downgrade) subscription
     *
     * @param array $params
     *   - newTier: (required) New subscription tier
     *   - paymentMethodId: (optional) Payment method ID for upgrades
     *   - billingCycle: (optional) 'monthly' or 'yearly'
     * @return array Updated subscription and message
     */
    public function change(array $params): array
    {
        return $this->client->request('PUT', '/api/v1/ai/subscriptions/change', [
            'json' => $params,
        ]);
    }

    /**
     * Cancel subscription
     *
     * @param string|null $reason Optional cancellation reason
     */
    public function cancel(?string $reason = null): void
    {
        $body = [];
        if ($reason) {
            $body['reason'] = $reason;
        }

        $this->client->request('DELETE', '/api/v1/ai/subscriptions/cancel', [
            'json' => $body,
        ]);
    }

    /**
     * Get AI usage statistics
     *
     * @return array Usage data including limits, usage counts, percentages, and reset times
     */
    public function getAiUsage(): array
    {
        return $this->client->request('GET', '/api/v1/ai/subscriptions/ai-usage');
    }
}
