<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class AnthropicService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->model  = config('services.anthropic.model', 'claude-sonnet-4-6');
    }

    public function analyzePayments(int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        $payments = Payment::with(['customer', 'product'])
            ->where('created_at', '>=', $since)
            ->get();

        $totalRevenue  = $payments->where('status', 'paid')->sum('amount');
        $totalOrders   = $payments->count();
        $paidOrders    = $payments->where('status', 'paid')->count();
        $pendingOrders = $payments->where('status', 'pending')->count();

        $productBreakdown = $payments->where('status', 'paid')
            ->groupBy('product_id')
            ->map(fn ($group) => [
                'name'    => optional($group->first()->product)->name ?? 'Unknown',
                'count'   => $group->count(),
                'revenue' => $group->sum('amount'),
            ])->values();

        $domainBreakdown = $payments->where('status', 'paid')
            ->groupBy(fn ($p) => optional($p->customer)->domain ?? 'unknown')
            ->map(fn ($group) => ['domain' => $group->first()->customer->domain ?? '?', 'count' => $group->count()])
            ->values()
            ->sortByDesc('count')
            ->take(10);

        $context = "You are a business analyst for Advernology Service, an email migration company serving former Lumos internet customers.\n\n"
            . "Payment data for the last {$days} days:\n"
            . "- Total orders: {$totalOrders}\n"
            . "- Paid orders: {$paidOrders}\n"
            . "- Pending orders: {$pendingOrders}\n"
            . "- Total revenue: $" . number_format($totalRevenue, 2) . "\n\n"
            . "Product breakdown:\n" . $productBreakdown->map(fn ($p) => "  - {$p['name']}: {$p['count']} orders, $" . number_format($p['revenue'], 2))->implode("\n") . "\n\n"
            . "Top customer domains:\n" . $domainBreakdown->map(fn ($d) => "  - {$d['domain']}: {$d['count']} customers")->implode("\n") . "\n\n"
            . "Respond ONLY with a valid JSON object (no markdown, no explanation outside the JSON) in this exact shape:\n"
            . "{\n"
            . "  \"summary\": \"One concise sentence summarising the overall business health.\",\n"
            . "  \"opportunities\": [\n"
            . "    {\"title\": \"Short title\", \"detail\": \"1-2 sentence explanation.\", \"priority\": \"high|medium|low\"}\n"
            . "  ],\n"
            . "  \"performance_highlights\": [\"Positive bullet 1\", \"Positive bullet 2\"],\n"
            . "  \"performance_concerns\": [\"Concern 1\", \"Concern 2\"],\n"
            . "  \"product_insights\": [\n"
            . "    {\"product\": \"Product name\", \"insight\": \"Why it is or isn't performing.\"}\n"
            . "  ],\n"
            . "  \"recommendations\": [\n"
            . "    {\"action\": \"Specific action to take.\", \"priority\": \"high|medium|low\", \"timeframe\": \"This week|This month|Next quarter\"}\n"
            . "  ]\n"
            . "}\n"
            . "Sort opportunities and recommendations highest-priority first. Be specific, concise, and actionable.";

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => 2048,
            'messages'   => [
                ['role' => 'user', 'content' => $context],
            ],
        ]);

        if ($response->failed()) {
            return ['_raw' => 'Error connecting to AI service: ' . $response->body()];
        }

        $text = $response->json('content.0.text', '');

        // Strip any markdown fences Claude may have added
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);

        $parsed = json_decode(trim($text), true);

        return is_array($parsed) ? $parsed : ['_raw' => $text ?: 'No response received.'];
    }
}
