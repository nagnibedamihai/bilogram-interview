<?php

namespace App\Services;

use App\Jobs\SendAlertJob;
use App\Jobs\SendNotificationJob;
use App\Models\Record;
use Illuminate\Database\UniqueConstraintViolationException;

class RecordProcessingService
{
    /**
     * Alert threshold value - records with value above this trigger alerts.
     * This would normally come from config: config('records.alert_threshold')
     */
    private const ALERT_THRESHOLD = 1000.00;

    /**
     * Process a record with idempotency check.
     *
     * @param  array  $data  The record data from request
     * @return array ['success' => bool, 'record' => Record|null, 'status' => int, 'message' => string]
     */
    public function processRecord(array $data): array
    {
        try {
            // Check if record already exists (idempotency)
            $existingRecord = Record::where('record_id', $data['recordId'])->first();

            if ($existingRecord) {
                return [
                    'success' => true,
                    'record' => $existingRecord,
                    'status' => 200,
                    'message' => 'Record already processed (idempotent response)',
                    'is_duplicate' => true,
                ];
            }

            // Create the record with snake_case fields
            $recordData = [
                'record_id' => $data['recordId'],
                'time' => $data['time'],
                'source_id' => $data['sourceId'],
                'destination_id' => $data['destinationId'],
                'type' => $data['type'],
                'value' => $data['value'],
                'unit' => $data['unit'],
                'reference' => $data['reference'],
            ];

            $record = Record::create($recordData);

            // Dispatch notification job (async)
            SendNotificationJob::dispatch($record);

            // Dispatch alert job if value exceeds threshold (async)
            if ((float) $record->value > self::ALERT_THRESHOLD) {
                SendAlertJob::dispatch($record, self::ALERT_THRESHOLD);
            }

            return [
                'success' => true,
                'record' => $record,
                'status' => 201,
                'message' => 'Record processed successfully',
                'is_duplicate' => false,
            ];
        } catch (UniqueConstraintViolationException $e) {
            // Race condition: another request processed this record simultaneously
            $existingRecord = Record::where('record_id', $data['recordId'])->first();

            return [
                'success' => true,
                'record' => $existingRecord,
                'status' => 200,
                'message' => 'Record already processed (race condition handled)',
                'is_duplicate' => true,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'record' => null,
                'status' => 500,
                'message' => 'Error processing record: '.$e->getMessage(),
                'is_duplicate' => false,
            ];
        }
    }
}
