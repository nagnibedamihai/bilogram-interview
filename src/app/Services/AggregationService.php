<?php

namespace App\Services;

use App\Models\Record;
use Illuminate\Support\Collection;

class AggregationService
{
    /**
     * Query records with filtering and grouping.
     *
     * @param  array  $filters  ['start_time' => ?, 'end_time' => ?, 'type' => ?]
     * @return array ['records' => Collection, 'groups' => Collection]
     */
    public function aggregate(array $filters = []): array
    {
        $query = Record::query();

        // Apply time range filter
        if (! empty($filters['start_time'])) {
            $query->where('time', '>=', $filters['start_time']);
        }

        if (! empty($filters['end_time'])) {
            $query->where('time', '<=', $filters['end_time']);
        }

        // Apply type filter
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Get all matching records
        $records = $query->orderBy('time')->get();

        // Group by destination_id and calculate totals
        $groups = $records->groupBy('destination_id')->map(function (Collection $group) {
            return [
                'destination_id' => $group->first()->destination_id,
                'count' => $group->count(),
                'total_value' => $group->sum('value'),
                'records' => $group,
            ];
        });

        return [
            'records' => $records,
            'groups' => $groups,
        ];
    }
}
