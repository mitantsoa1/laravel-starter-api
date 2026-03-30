<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UmamiController extends Controller
{
    private ?string $baseUrl;

    private ?string $token;

    private ?string $websiteId;

    public function __construct()
    {
        $this->baseUrl = config('services.umami.url');
        $this->token = config('services.umami.token');
        $this->websiteId = config('services.umami.website_id');
    }

    private function request(string $endpoint, array $params = [])
    {
        if (empty($this->baseUrl) || empty($this->token) || empty($this->websiteId)) {
            return response()->json(['error' => 'Umami configuration missing'], 500);
        }

        $url = rtrim($this->baseUrl, '/').str_replace(':websiteId', $this->websiteId, $endpoint);

        try {
            $response = Http::withHeaders([
                'x-umami-api-key' => $this->token,
                'Content-Type' => 'application/json',
            ])->get($url, $params);

            if ($response->failed()) {
                Log::error("Umami API error at endpoint {$endpoint}: ".$response->body());

                return response()->json([
                    'error' => 'Umami API error',
                    'message' => $response->json('message') ?? $response->reason(),
                ], $response->status());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Umami connection error at endpoint {$endpoint}: ".$e->getMessage());

            return response()->json(['error' => 'Umami connection error'], 500);
        }
    }

    public function getStats(Request $request)
    {
        return $this->request('/websites/:websiteId/stats', $request->all());
    }

    public function getPageViews(Request $request)
    {
        return $this->request('/websites/:websiteId/pageviews', $request->all());
    }

    public function getMetrics(Request $request)
    {
        return $this->request('/websites/:websiteId/metrics', $request->all());
    }

    public function getExpandedMetrics(Request $request)
    {
        return $this->request('/websites/:websiteId/metrics/expanded', $request->all());
    }

    public function getEvents(Request $request)
    {
        return $this->request('/websites/:websiteId/events', $request->all());
    }

    public function getEventSeries(Request $request)
    {
        return $this->request('/websites/:websiteId/events/series', $request->all());
    }

    public function getActive(Request $request)
    {
        return $this->request('/websites/:websiteId/active', $request->all());
    }
}
