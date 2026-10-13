<?php

declare(strict_types=1);

namespace Slendie\ReactAdmin\Http\Controllers\Api;

use DateTimeImmutable;
use Slendie\Http\JsonResponse;
use Slendie\ReactAdmin\Support\Leads;

final class StatsController
{
    public function __invoke(): JsonResponse
    {
        $model = Leads::model();

        $byStatus = [];
        foreach (Leads::STATUSES as $status) {
            $byStatus[$status] = $model::query()->where('status', $status)->count();
        }

        $days = [];
        $today = new DateTimeImmutable('today');
        for ($i = 6; $i >= 0; $i--) {
            $start = $today->modify("-{$i} days");
            $days[] = [
                'date' => $start->format('Y-m-d'),
                'count' => $model::query()
                    ->where('created_at', '>=', $start->format('Y-m-d H:i:s'))
                    ->where('created_at', '<', $start->modify('+1 day')->format('Y-m-d H:i:s'))
                    ->count(),
            ];
        }

        return response()->json([
            'total' => $model::query()->count(),
            'by_status' => $byStatus,
            'last_7_days' => $days,
        ]);
    }
}
