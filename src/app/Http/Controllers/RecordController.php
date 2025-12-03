<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecordRequest;
use App\Services\AggregationService;
use App\Services\RecordProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function __construct(
        private readonly RecordProcessingService $recordProcessingService,
        private readonly AggregationService $aggregationService,
    ) {}

    /**
     * Store a new record or return existing if duplicate (idempotent).
     */
    public function store(StoreRecordRequest $request): JsonResponse
    {
        $result = $this->recordProcessingService->processRecord($request->validated());

        if (! $result['success']) {
            return response()->json(
                [
                    'message' => $result['message'],
                    'errors' => [],
                ],
                $result['status']
            );
        }

        return response()->json(
            [
                'message' => $result['message'],
                'data' => [
                    'id' => $result['record']->id,
                    'recordId' => $result['record']->record_id,
                    'time' => $result['record']->time,
                    'sourceId' => $result['record']->source_id,
                    'destinationId' => $result['record']->destination_id,
                    'type' => $result['record']->type,
                    'value' => (string) $result['record']->value,
                    'unit' => $result['record']->unit,
                    'reference' => $result['record']->reference,
                    'isDuplicate' => $result['is_duplicate'],
                ],
            ],
            $result['status']
        );
    }

    /**
     * Get aggregated records with optional filters and grouping by destinationId.
     *
     * Query parameters:
     * - startTime: ISO 8601 datetime (optional)
     * - endTime: ISO 8601 datetime (optional)
     * - type: 'positive' or 'negative' (optional)
     */
    public function aggregate(Request $request): JsonResponse
    {
        $filters = [
            'start_time' => $request->query('startTime'),
            'end_time' => $request->query('endTime'),
            'type' => $request->query('type'),
        ];

        // Validate type if provided
        if (! empty($filters['type']) && ! in_array($filters['type'], ['positive', 'negative'])) {
            return response()->json([
                'message' => 'Invalid type. Must be "positive" or "negative".',
                'errors' => ['type' => ['Invalid type value']],
            ], 422);
        }

        $result = $this->aggregationService->aggregate($filters);

        return response()->json([
            'message' => 'Records aggregated successfully',
            'data' => [
                'count' => $result['records']->count(),
                'records' => $result['records']->map(fn ($record) => [
                    'id' => $record->id,
                    'recordId' => $record->record_id,
                    'time' => $record->time,
                    'sourceId' => $record->source_id,
                    'destinationId' => $record->destination_id,
                    'type' => $record->type,
                    'value' => (string) $record->value,
                    'unit' => $record->unit,
                    'reference' => $record->reference,
                ])->values(),
                'groups' => $result['groups']->map(fn ($group) => [
                    'destinationId' => $group['destination_id'],
                    'recordCount' => $group['count'],
                    'totalValue' => (string) $group['total_value'],
                ])->values(),
            ],
        ], 200);
    }
}
