<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'updated_after' => ['nullable', 'date'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 200);

        $query = Guest::query()->orderBy('id');

        if (! empty($validated['updated_after'])) {
            $updatedAfter = Carbon::parse($validated['updated_after'])->toDateTimeString();
            $query->where('updated_at', '>', $updatedAfter);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator
                ->getCollection()
                ->map(fn (Guest $guest) => $this->toResponseItem($guest))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $keyword = trim((string) $validated['keyword']);
        $limit = (int) ($validated['limit'] ?? 8);

        if ($keyword === '') {
            return response()->json([
                'data' => [],
            ]);
        }

        $rows = Guest::query()
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('name')
            ->orderBy('division')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (Guest $guest) => $this->toResponseItem($guest))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'division' => ['required', 'string', 'max:120'],
        ]);

        $name = trim((string) $validated['name']);
        $division = trim((string) $validated['division']);

        $guest = Guest::query()->firstOrCreate([
            'name' => $name,
            'division' => $division,
        ]);

        return response()->json([
            'data' => $this->toResponseItem($guest),
            'created' => $guest->wasRecentlyCreated,
        ], $guest->wasRecentlyCreated ? 201 : 200);
    }

    private function toResponseItem(Guest $guest): array
    {
        return [
            'id' => $guest->id,
            'name' => $guest->name,
            'division' => $guest->division,
            'created_at' => optional($guest->created_at)->toIso8601String(),
            'updated_at' => optional($guest->updated_at)->toIso8601String(),
        ];
    }
}
