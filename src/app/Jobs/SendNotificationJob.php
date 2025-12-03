<?php

namespace App\Jobs;

use App\Models\Record;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Record $record,
    ) {}

    /**
     * Execute the job.
     *
     * Emits a message containing:
     * - The processed record
     * - Summary of previous records with same destinationId and reference
     */
    public function handle(): void
    {
        // Get summary of previous records for same destination + reference
        $previousRecords = Record::where('destination_id', $this->record->destination_id)
            ->where('reference', $this->record->reference)
            ->where('id', '!=', $this->record->id)
            ->get();

        $summary = [
            'destination_id' => $this->record->destination_id,
            'reference' => $this->record->reference,
            'count' => $previousRecords->count(),
            'total_value' => $previousRecords->sum('value'),
            'positive_count' => $previousRecords->where('type', 'positive')->count(),
            'negative_count' => $previousRecords->where('type', 'negative')->count(),
            'positive_total' => $previousRecords->where('type', 'positive')->sum('value'),
            'negative_total' => $previousRecords->where('type', 'negative')->sum('value'),
        ];

        $notification = [
            'record' => [
                'id' => $this->record->id,
                'record_id' => $this->record->record_id,
                'time' => $this->record->time,
                'source_id' => $this->record->source_id,
                'destination_id' => $this->record->destination_id,
                'type' => $this->record->type,
                'value' => $this->record->value,
                'unit' => $this->record->unit,
                'reference' => $this->record->reference,
            ],
            'summary' => $summary,
        ];

        // TODO: Emit message to notification service
        // This could be:
        // - Redis PUBLISH to a channel
        // - Message broker (RabbitMQ, SQS, etc.)
        // - Database event log
        // - HTTP webhook to notification service

        // For now, we'll log it as a placeholder
        \Log::info('Notification sent', $notification);
    }
}
