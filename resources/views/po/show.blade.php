@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <a href="{{ route('po.index') }}" class="text-sm text-blue-600 underline">&larr; Kembali</a>
    </div>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold">Detail PO</h1>
                <div class="text-sm text-gray-600 mt-1">
                    <span>No. PO: </span>
                    <span class="font-medium">{{ $poUpload->po_no ?? '—' }}</span>
                </div>
                <div class="text-sm text-gray-600">
                    <span>Diunggah: </span>
                    <span class="font-medium">{{ $poUpload->created_at->format('Y-m-d H:i') }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a class="px-3 py-2 rounded-md border" href="{{ $poUpload->file_url }}" target="_blank">Lihat PDF</a>
                <span
                    class="px-2 py-1 rounded text-xs {{ $poUpload->status === 'OK' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $poUpload->status }}
                </span>
                <form action="{{ route('po.destroy', $poUpload) }}" method="POST"
                    onsubmit="return confirm('Hapus data PO ini? Tindakan tidak dapat dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button class="px-3 py-2 rounded-md border border-red-300 text-red-700 hover:bg-red-50">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if ($summary)
        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <h2 class="font-semibold mb-3 text-sm">Ringkasan</h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-sm">
                @foreach ($summary as $k => $v)
                    @continue($k === '__summary')
                    <div class="p-3 rounded border bg-gray-50">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500">{{ $k }}</div>
                        <div class="font-medium break-words">
                            @if (is_array($v))
                                {{ json_encode($v, JSON_UNESCAPED_UNICODE) }}
                            @else
                                {{ $v }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">Part No</th>
                    <th class="text-right px-4 py-2">Harga PO</th>
                    <th class="text-right px-4 py-2">Harga PC</th>
                    <th class="text-right px-4 py-2">Diff</th>
                    <th class="text-right px-4 py-2">Diff %</th>
                    <th class="text-left px-4 py-2">Status</th>
                    <th class="text-left px-4 py-2">Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $it)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">{{ $it['partNo'] ?? '—' }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $it['unitPrice_PO'] ?? '—' }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $it['UnitPrice_master'] ?? '—' }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $it['diff'] ?? '—' }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">
                            @if (isset($it['diffPct']))
                                {{ is_numeric($it['diffPct']) ? number_format($it['diffPct'], 2) : $it['diffPct'] }}%
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @php $st = strtolower((string)($it['status'] ?? '')); @endphp
                            <span
                                class="px-2 py-1 rounded text-xs
              @if ($st === 'match') bg-emerald-100 text-emerald-700
              @elseif($st === 'mismatch') bg-red-100 text-red-700
              @else bg-gray-100 text-gray-700 @endif">
                                {{ $it['status'] ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $it['note'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">Tidak ada item dari n8n.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (empty($items))
        <div class="mt-6 bg-white rounded-xl shadow p-4">
            <h3 class="font-semibold mb-2 text-sm">Raw response (debug)</h3>
            <pre class="bg-gray-50 border rounded p-3 text-xs overflow-x-auto">
{{ json_encode($poUpload->n8n_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
    </pre>
        </div>
    @endif
@endsection
