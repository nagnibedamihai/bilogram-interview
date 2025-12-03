<?php

namespace Tests\Feature;

use App\Models\Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test storing a new record.
     */
    public function test_store_record_creates_new_record(): void
    {
        $payload = [
            'recordId' => 'rec-001',
            'time' => '2025-01-01 10:00:00',
            'sourceId' => 'source-1',
            'destinationId' => 'dest-1',
            'type' => 'positive',
            'value' => '100.50',
            'unit' => 'EUR',
            'reference' => 'ref-001',
        ];

        $response = $this->postJson('/api/records', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Record processed successfully',
                'data' => [
                    'recordId' => 'rec-001',
                    'sourceId' => 'source-1',
                    'destinationId' => 'dest-1',
                    'type' => 'positive',
                    'value' => '100.50',
                    'unit' => 'EUR',
                    'reference' => 'ref-001',
                    'isDuplicate' => false,
                ],
            ]);

        $this->assertDatabaseHas('records', [
            'record_id' => 'rec-001',
            'source_id' => 'source-1',
            'destination_id' => 'dest-1',
        ]);
    }

    /**
     * Test idempotency - duplicate record returns 200.
     */
    public function test_store_record_idempotent_duplicate_returns_200(): void
    {
        $payload = [
            'recordId' => 'rec-002',
            'time' => '2025-01-01 11:00:00',
            'sourceId' => 'source-2',
            'destinationId' => 'dest-2',
            'type' => 'negative',
            'value' => '50.25',
            'unit' => 'USD',
            'reference' => 'ref-002',
        ];

        // First request
        $response1 = $this->postJson('/api/records', $payload);
        $response1->assertStatus(201);

        // Duplicate request
        $response2 = $this->postJson('/api/records', $payload);
        $response2->assertStatus(200)
            ->assertJson([
                'message' => 'Record already processed (idempotent response)',
                'data' => [
                    'recordId' => 'rec-002',
                    'isDuplicate' => true,
                ],
            ]);

        // Only one record should exist in database
        $this->assertCount(1, Record::where('record_id', 'rec-002')->get());
    }

    /**
     * Test validation - missing required fields.
     */
    public function test_store_record_validation_missing_fields(): void
    {
        $payload = [
            'recordId' => 'rec-003',
            // Missing other required fields
        ];

        $response = $this->postJson('/api/records', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'time',
                'sourceId',
                'destinationId',
                'type',
                'value',
                'unit',
                'reference',
            ]);
    }

    /**
     * Test validation - invalid type.
     */
    public function test_store_record_validation_invalid_type(): void
    {
        $payload = [
            'recordId' => 'rec-004',
            'time' => '2025-01-01 12:00:00',
            'sourceId' => 'source-4',
            'destinationId' => 'dest-4',
            'type' => 'invalid-type',
            'value' => '100.00',
            'unit' => 'EUR',
            'reference' => 'ref-004',
        ];

        $response = $this->postJson('/api/records', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    /**
     * Test validation - invalid datetime format.
     */
    public function test_store_record_validation_invalid_datetime(): void
    {
        $payload = [
            'recordId' => 'rec-005',
            'time' => 'invalid-date',
            'sourceId' => 'source-5',
            'destinationId' => 'dest-5',
            'type' => 'positive',
            'value' => '100.00',
            'unit' => 'EUR',
            'reference' => 'ref-005',
        ];

        $response = $this->postJson('/api/records', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('time');
    }

    /**
     * Test storing multiple records with different IDs.
     */
    public function test_store_multiple_records(): void
    {
        $basePayload = [
            'time' => '2025-01-01 13:00:00',
            'sourceId' => 'source-multi',
            'destinationId' => 'dest-multi',
            'type' => 'positive',
            'value' => '100.00',
            'unit' => 'EUR',
            'reference' => 'ref-multi',
        ];

        for ($i = 1; $i <= 5; $i++) {
            $payload = array_merge($basePayload, ['recordId' => "rec-multi-{$i}"]);
            $response = $this->postJson('/api/records', $payload);
            $response->assertStatus(201);
        }

        $this->assertCount(5, Record::all());
    }

    /**
     * Test aggregation without filters returns all records.
     */
    public function test_aggregate_without_filters(): void
    {
        // Create test data
        $this->createTestRecords([
            ['recordId' => 'agg-001', 'destinationId' => 'dest-1', 'type' => 'positive', 'value' => '100.00'],
            ['recordId' => 'agg-002', 'destinationId' => 'dest-1', 'type' => 'positive', 'value' => '50.00'],
            ['recordId' => 'agg-003', 'destinationId' => 'dest-2', 'type' => 'negative', 'value' => '-25.00'],
        ]);

        $response = $this->getJson('/api/records/aggregate');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(3, $data['count']);
        $this->assertCount(3, $data['records']);

        // Check groups
        $groups = collect($data['groups'])->keyBy('destinationId');
        $this->assertCount(2, $groups);

        // dest-1 should have 2 records with total 150
        $this->assertEquals('150', $groups['dest-1']['totalValue']);
        $this->assertEquals(2, $groups['dest-1']['recordCount']);

        // dest-2 should have 1 record with total -25
        $this->assertEquals('-25', $groups['dest-2']['totalValue']);
        $this->assertEquals(1, $groups['dest-2']['recordCount']);
    }

    /**
     * Test aggregation with type filter.
     */
    public function test_aggregate_with_type_filter(): void
    {
        $this->createTestRecords([
            ['recordId' => 'type-001', 'destinationId' => 'dest-1', 'type' => 'positive', 'value' => '100.00'],
            ['recordId' => 'type-002', 'destinationId' => 'dest-1', 'type' => 'negative', 'value' => '50.00'],
            ['recordId' => 'type-003', 'destinationId' => 'dest-2', 'type' => 'positive', 'value' => '75.00'],
        ]);

        $response = $this->getJson('/api/records/aggregate?type=positive');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'count' => 2,
                    'groups' => [
                        ['destinationId' => 'dest-1', 'recordCount' => 1, 'totalValue' => '100.00'],
                        ['destinationId' => 'dest-2', 'recordCount' => 1, 'totalValue' => '75.00'],
                    ],
                ],
            ]);
    }

    /**
     * Test aggregation with time range filter.
     */
    public function test_aggregate_with_time_range_filter(): void
    {
        $this->createTestRecords([
            ['recordId' => 'time-001', 'time' => '2025-01-01 10:00:00', 'destinationId' => 'dest-1', 'type' => 'positive', 'value' => '100.00'],
            ['recordId' => 'time-002', 'time' => '2025-01-02 10:00:00', 'destinationId' => 'dest-1', 'type' => 'positive', 'value' => '50.00'],
            ['recordId' => 'time-003', 'time' => '2025-01-03 10:00:00', 'destinationId' => 'dest-1', 'type' => 'positive', 'value' => '75.00'],
        ]);

        $response = $this->getJson('/api/records/aggregate?startTime=2025-01-02%2010:00:00&endTime=2025-01-03%2010:00:00');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'count' => 2,
                    'groups' => [
                        ['destinationId' => 'dest-1', 'recordCount' => 2, 'totalValue' => '125.00'],
                    ],
                ],
            ]);
    }

    /**
     * Test aggregation with invalid type filter.
     */
    public function test_aggregate_with_invalid_type_filter(): void
    {
        $response = $this->getJson('/api/records/aggregate?type=invalid');

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid type. Must be "positive" or "negative".',
                'errors' => ['type' => ['Invalid type value']],
            ]);
    }

    /**
     * Test aggregation with empty database.
     */
    public function test_aggregate_empty_database(): void
    {
        $response = $this->getJson('/api/records/aggregate');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'count' => 0,
                    'records' => [],
                    'groups' => [],
                ],
            ]);
    }

    /**
     * Helper method to create test records.
     */
    private function createTestRecords(array $records): void
    {
        foreach ($records as $record) {
            $defaultRecord = [
                'time' => '2025-01-01 10:00:00',
                'sourceId' => 'source-test',
                'unit' => 'EUR',
                'reference' => 'ref-test',
            ];

            $payload = array_merge($defaultRecord, $record);
            $this->postJson('/api/records', $payload);
        }
    }
}
