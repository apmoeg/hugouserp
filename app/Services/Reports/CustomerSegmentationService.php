<?php

namespace App\Services\Reports;

use App\Models\Customer;

class CustomerSegmentationService
{
    /**
     * Get customer segmentation analysis with RFM scoring
     * (Recency, Frequency, Monetary)
     */
    public function getSegmentation()
    {
        $customers = Customer::select('customers.*')
            ->selectRaw('COUNT(sales.id) as purchase_frequency')
            ->selectRaw('MAX(sales.posted_at) as last_purchase_date')
            ->selectRaw('DATEDIFF(NOW(), MAX(sales.posted_at)) as recency_days')
            ->leftJoin('sales', 'customers.id', '=', 'sales.customer_id')
            ->whereNull('sales.deleted_at')
            ->groupBy('customers.id')
            ->get();

        $segments = [
            'champions' => [],
            'loyal' => [],
            'at_risk' => [],
            'lost' => [],
            'new' => [],
        ];

        foreach ($customers as $customer) {
            $segment = $this->calculateSegment($customer);
            $segments[$segment][] = [
                'id' => $customer->id,
                'name' => $customer->name,
                'lifetime_revenue' => $customer->lifetime_revenue ?? 0,
                'purchase_frequency' => $customer->purchase_frequency,
                'recency_days' => $customer->recency_days ?? 999,
                'churn_risk' => $this->calculateChurnRisk($customer),
            ];
        }

        return [
            'segments' => $segments,
            'summary' => $this->getSegmentSummary($segments),
        ];
    }

    /**
     * Calculate customer segment based on RFM model
     */
    private function calculateSegment($customer)
    {
        $recency = $customer->recency_days ?? 999;
        $frequency = $customer->purchase_frequency ?? 0;
        $monetary = $customer->lifetime_revenue ?? 0;

        // Champions: Recent, frequent, high value
        if ($recency <= 30 && $frequency >= 5 && bccomp((string) $monetary, '10000', 2) >= 0) {
            return 'champions';
        }

        // Loyal: Frequent purchases, good value
        if ($frequency >= 3 && bccomp((string) $monetary, '5000', 2) >= 0) {
            return 'loyal';
        }

        // At Risk: Used to buy frequently but haven't recently
        if ($recency > 60 && $recency <= 180 && $frequency >= 2) {
            return 'at_risk';
        }

        // Lost: Haven't purchased in 6+ months
        if ($recency > 180) {
            return 'lost';
        }

        // New: Recent first purchase
        return 'new';
    }

    /**
     * Calculate churn risk percentage
     */
    private function calculateChurnRisk($customer)
    {
        $recency = $customer->recency_days ?? 999;
        $frequency = $customer->purchase_frequency ?? 0;

        // High risk: No purchase in 180+ days
        if ($recency > 180) {
            return 90;
        }

        // Medium-high risk: No purchase in 90-180 days
        if ($recency > 90) {
            return 60;
        }

        // Medium risk: No purchase in 60-90 days, low frequency
        if ($recency > 60 && $frequency < 3) {
            return 40;
        }

        // Low risk: Recent activity
        return 10;
    }

    /**
     * Get segment summary statistics
     */
    private function getSegmentSummary($segments)
    {
        $summary = [];

        foreach ($segments as $name => $customers) {
            $totalRevenue = '0';
            foreach ($customers as $customer) {
                $totalRevenue = bcadd($totalRevenue, (string) $customer['lifetime_revenue'], 2);
            }

            $summary[$name] = [
                'count' => count($customers),
                'total_revenue' => (float) $totalRevenue,
                'avg_revenue' => count($customers) > 0
                    ? (float) bcdiv($totalRevenue, (string) count($customers), 2)
                    : 0,
            ];
        }

        return $summary;
    }

    /**
     * Get churn analysis with predictions
     */
    public function getChurnAnalysis()
    {
        $at_risk = Customer::select('customers.*')
            ->selectRaw('DATEDIFF(NOW(), MAX(sales.posted_at)) as days_since_purchase')
            ->leftJoin('sales', 'customers.id', '=', 'sales.customer_id')
            ->whereNull('sales.deleted_at')
            ->groupBy('customers.id')
            ->havingRaw('days_since_purchase > 60')
            ->orderBy('days_since_purchase', 'desc')
            ->limit(50)
            ->get();

        return [
            'at_risk_customers' => $at_risk->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'days_since_purchase' => $customer->days_since_purchase,
                    'lifetime_revenue' => $customer->lifetime_revenue ?? 0,
                    'churn_probability' => min(90, ($customer->days_since_purchase / 365) * 100),
                    'recommended_action' => $this->getRecommendedAction($customer),
                ];
            }),
            'total_at_risk' => $at_risk->count(),
            'revenue_at_risk' => (float) $at_risk->sum('lifetime_revenue'),
        ];
    }

    /**
     * Get recommended action for at-risk customer
     */
    private function getRecommendedAction($customer)
    {
        $days = $customer->days_since_purchase ?? 0;

        if ($days > 180) {
            return 'Win-back campaign with special offer';
        }

        if ($days > 90) {
            return 'Re-engagement email with product recommendations';
        }

        return 'Check-in call or personalized email';
    }
}
