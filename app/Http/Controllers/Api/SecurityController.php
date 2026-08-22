<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSecurityEventsRequest;
use App\Services\Alerting\SecurityAlertEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    public function __construct(private readonly SecurityAlertEvaluator $alertEvaluator) {}

    /**
     * Ingest a batch of ML-flagged security events from the Python daemon's
     * capture pipeline (Phase 5) — the security-alert analogue of
     * MonitoringController::storeMetrics().
     */
    public function storeEvents(StoreSecurityEventsRequest $request): JsonResponse
    {
        $events = $request->validated('events');

        $accepted = DB::transaction(function () use ($events) {
            foreach ($events as $event) {
                $this->alertEvaluator->evaluate($event);
            }

            return count($events);
        });

        return response()->json(['accepted' => $accepted], 201);
    }
}
