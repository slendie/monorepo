<?php

declare(strict_types=1);

namespace Slendie\ReactAdmin\Http\Controllers\Api;

use Slendie\Http\JsonResponse;
use Slendie\Http\Request;
use Slendie\Http\Response;
use Slendie\Model\Model;
use Slendie\ReactAdmin\Support\Leads;

/** CRUD de leads do painel. Serve de modelo para adaptar o painel a outro model (ver docs/react-admin.md). */
final class LeadController
{
    public function index(Request $request): JsonResponse
    {
        $query = Leads::model()::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($q) => $q->whereLike('name', $term)->orWhereLike('email', $term));
        }

        $sort = $request->string('sort', 'created_at');
        $sort = in_array($sort, Leads::SORTABLE, true) ? $sort : 'created_at';
        $direction = strtolower($request->string('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(100, max(1, $request->integer('per_page', 15)));

        $query->orderBy($sort, $direction);

        if ($sort !== 'created_at') {
            $query->orderBy('id', 'desc');
        }

        return response()->json($query->paginate($perPage)->toArray());
    }

    public function show(string $lead): JsonResponse
    {
        return response()->json(['data' => $this->find($lead)]);
    }

    public function update(Request $request, string $lead): JsonResponse
    {
        $model = $this->find($lead);

        $data = $request->validate([
            'status' => 'sometimes|required|in:'.implode(',', Leads::STATUSES),
            'notes' => 'sometimes|nullable|string|max:5000',
        ]);

        $model->forceFill($data)->save();

        return response()->json(['data' => $model]);
    }

    public function destroy(string $lead): Response
    {
        $this->find($lead)->delete();

        return response()->noContent();
    }

    private function find(string $id): Model
    {
        /** @var Model */
        return Leads::model()::query()->findOrFail($id);
    }
}
