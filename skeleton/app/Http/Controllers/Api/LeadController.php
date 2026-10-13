<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Slendie\Http\JsonResponse;
use Slendie\Http\Request;
use Slendie\Http\Response;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::filtered(
            $request->filled('status') ? $request->string('status') : null,
            $request->filled('search') ? $request->string('search') : null,
        );

        $perPage = min(100, max(1, $request->integer('per_page', 15)));

        return response()->json($query->paginate($perPage));
    }

    public function show(Lead $lead): JsonResponse
    {
        return response()->json(['data' => $lead]);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', Lead::STATUSES),
        ]);

        $lead->update($data);

        return response()->json(['data' => $lead]);
    }

    public function destroy(Lead $lead): Response
    {
        $lead->delete();

        return response()->noContent();
    }
}
