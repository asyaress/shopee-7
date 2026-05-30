@php
    $map = [
        'pending' => ['Menunggu', 'hub-pill-warning'],
        'in_progress' => ['Proses', 'hub-pill-muted'],
        'completed' => ['Selesai', 'hub-pill-success'],
        'cancelled' => ['Batal', 'hub-pill-danger'],
    ];
    $row = $map[$status ?? ''] ?? [ucfirst($status ?? '-'), 'hub-pill-muted'];
@endphp
<span class="hub-pill {{ $row[1] }}">{{ $row[0] }}</span>
