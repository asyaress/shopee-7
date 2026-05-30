<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GuestCheckin;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CheckinController extends Controller
{
    private const MAX_SIGNATURE_BYTES = 524288;

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:100'],
            'sent_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.client_uuid' => ['required', 'uuid'],
            'items.*.name' => ['required', 'string', 'max:120'],
            'items.*.division' => ['required', 'string', 'max:120'],
            'items.*.arrived_at' => ['required', 'date'],
            'items.*.signature_png_base64' => ['required', 'string'],
            'items.*.signature_sha256' => ['nullable', 'string', 'size:64'],
        ]);

        $summary = [
            'total' => count($validated['items']),
            'accepted' => 0,
            'duplicate' => 0,
            'failed' => 0,
        ];

        $results = [];
        $disk = $this->signatureDisk();

        foreach ($validated['items'] as $item) {
            $clientUuid = $item['client_uuid'];

            try {
                $existing = GuestCheckin::query()
                    ->where('client_uuid', $clientUuid)
                    ->first();

                if ($existing) {
                    $summary['duplicate']++;
                    $results[] = [
                        'client_uuid' => $clientUuid,
                        'status' => 'duplicate',
                        'server_id' => $existing->id,
                    ];
                    continue;
                }

                [$signatureBinary, $signatureSha256] = $this->decodeSignature($item['signature_png_base64']);

                if (! empty($item['signature_sha256']) && strtolower($item['signature_sha256']) !== $signatureSha256) {
                    throw new \RuntimeException('signature_sha256 mismatch');
                }

                $folderDate = now();
                $signaturePath = sprintf(
                    '%s/%s/%s.png',
                    $folderDate->format('Y'),
                    $folderDate->format('m'),
                    $clientUuid
                );

                $stored = Storage::disk($disk)->put($signaturePath, $signatureBinary);

                if (! $stored) {
                    throw new \RuntimeException('failed to store signature');
                }

                try {
                    DB::beginTransaction();

                    $guestCheckin = GuestCheckin::query()->create([
                        'client_uuid' => $clientUuid,
                        'name' => trim($item['name']),
                        'division' => trim($item['division']),
                        'arrived_at' => Carbon::parse($item['arrived_at'])->format('Y-m-d H:i:s'),
                        'signature_path' => $signaturePath,
                        'signature_sha256' => $signatureSha256,
                        'device_id' => $validated['device_id'],
                        'operator_id' => $request->user()->id,
                        'raw_payload' => [
                            'sent_at' => $validated['sent_at'] ?? null,
                            'item' => Arr::except($item, ['signature_png_base64']),
                        ],
                    ]);

                    DB::commit();
                } catch (\Throwable $exception) {
                    DB::rollBack();
                    Storage::disk($disk)->delete($signaturePath);
                    throw $exception;
                }

                $summary['accepted']++;
                $results[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'accepted',
                    'server_id' => $guestCheckin->id,
                ];
            } catch (QueryException $exception) {
                if ($this->isDuplicateException($exception)) {
                    $existing = GuestCheckin::query()
                        ->where('client_uuid', $clientUuid)
                        ->first();

                    $summary['duplicate']++;
                    $results[] = [
                        'client_uuid' => $clientUuid,
                        'status' => 'duplicate',
                        'server_id' => $existing?->id,
                    ];
                    continue;
                }

                $summary['failed']++;
                $results[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'failed',
                    'error' => 'database_error',
                ];
            } catch (\Throwable $exception) {
                $summary['failed']++;
                $results[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'failed',
                    'error' => Str::limit($exception->getMessage(), 120),
                ];
            }
        }

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'summary' => $summary,
            'results' => $results,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $perPage = $validated['per_page'] ?? 20;

        $query = GuestCheckin::query()->orderByDesc('arrived_at')->orderByDesc('id');

        if (! empty($validated['date_from'])) {
            $query->whereDate('arrived_at', '>=', Carbon::parse($validated['date_from'])->toDateString());
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('arrived_at', '<=', Carbon::parse($validated['date_to'])->toDateString());
        }

        $paginator = $query->paginate($perPage);
        $items = $paginator->getCollection()->map(fn (GuestCheckin $checkin) => $this->toResponseItem($checkin));

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(GuestCheckin $guestCheckin): JsonResponse
    {
        return response()->json([
            'data' => $this->toResponseItem($guestCheckin),
        ]);
    }

    public function signature(GuestCheckin $guestCheckin): StreamedResponse
    {
        $disk = $this->signatureDisk();
        $path = $guestCheckin->signature_path;

        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'Signature file not found.');
        }

        $stream = Storage::disk($disk)->readStream($path);

        if (! is_resource($stream)) {
            abort(404, 'Signature file not found.');
        }

        $filename = sprintf('%s.png', $guestCheckin->client_uuid);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function decodeSignature(string $encodedSignature): array
    {
        $signature = trim($encodedSignature);
        $signature = preg_replace('/^data:image\/png;base64,/i', '', $signature) ?? $signature;

        $binary = base64_decode($signature, true);

        if ($binary === false || $binary === '') {
            throw new \RuntimeException('signature base64 invalid');
        }

        if (strlen($binary) > self::MAX_SIGNATURE_BYTES) {
            throw new \RuntimeException('signature too large');
        }

        if (! $this->isPngBinary($binary)) {
            throw new \RuntimeException('signature is not valid PNG');
        }

        return [$binary, hash('sha256', $binary)];
    }

    private function isPngBinary(string $binary): bool
    {
        if (! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return false;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $mimeType = finfo_buffer($finfo, $binary);
                finfo_close($finfo);

                return $mimeType === 'image/png';
            }
        }

        return true;
    }

    private function isDuplicateException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        return $sqlState === '23000';
    }

    private function toResponseItem(GuestCheckin $checkin): array
    {
        return [
            'id' => $checkin->id,
            'client_uuid' => $checkin->client_uuid,
            'name' => $checkin->name,
            'division' => $checkin->division,
            'arrived_at' => optional($checkin->arrived_at)->toIso8601String(),
            'signature_path' => $checkin->signature_path,
            'signature_url' => route('api.v1.checkins.signature', ['guestCheckin' => $checkin->id]),
            'signature_sha256' => $checkin->signature_sha256,
            'device_id' => $checkin->device_id,
            'operator_id' => $checkin->operator_id,
            'created_at' => optional($checkin->created_at)->toIso8601String(),
            'updated_at' => optional($checkin->updated_at)->toIso8601String(),
        ];
    }

    private function signatureDisk(): string
    {
        return (string) config('services.buku_tamu.signature_disk', 'signatures');
    }
}
