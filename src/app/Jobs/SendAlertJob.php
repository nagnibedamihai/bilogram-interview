<?php

namespace App\Jobs;

use App\Models\Record;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAlertJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Record $record,
        private readonly float $threshold,
    ) {}

    /**
     * Execute the job.
     *
     * Emits an alert message when record value exceeds the configurable threshold.
     */
    public function handle(): void
    {
        if ($this->record->value <= $this->threshold) {
            // Threshold not exceeded, no alert needed
            return;
        }

        $alert = [
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
            'threshold' => $this->threshold,
            'exceeded_by' => $this->record->value - $this->threshold,
            'severity' => $this->calculateSeverity(),
        ];

        // TODO: Emit alert message to alerting service
        // This could be:
        // - Redis PUBLISH to a channel
        // - Message broker (RabbitMQ, SQS, etc.)
        // - Database event log
        // - HTTP webhook to alerting service

        // For now, we'll log it as a placeholder
        \Log::warning('High value alert triggered', $alert);
    }

    /**
     * Calculate severity level based on how much threshold was exceeded.
     */
    private function calculateSeverity(): string
    {
        $exceedance = $this->record->value - $this->threshold;
        $percentageExceeded = ($exceedance / $this->threshold) * 100;

        if ($percentageExceeded > 50) {
            return 'critical';
        } elseif ($percentageExceeded > 25) {
            return 'high';
        }

        return 'medium';
    }
}
