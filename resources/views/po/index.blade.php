@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-lg font-semibold">Riwayat Upload PO</h1>
        <a href="{{ route('po.create') }}" class="px-3 py-2 rounded-md bg-blue-600 text-white">Upload Baru</a>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        @if (session('bulk_results'))
            <details class="mb-4 bg-white rounded-xl shadow p-4">
                <summary class="cursor-pointer font-medium">Rincian hasil batch</summary>
                <ul class="mt-2 text-sm list-disc pl-5">
                    @foreach (session('bulk_results') as $r)
                        <li>
                            {{ $r['name'] }} —
                            @if (isset($r['error']))
                                <span class="text-red-700">ERROR: {{ $r['error'] }}</span>
                            @else
                                PO: {{ $r['po_no'] ?? '—' }} • Status: {{ $r['status'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif

        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2">Waktu</th>
                    <th class="text-left px-4 py-2">No. PO</th>
                    <th class="text-left px-4 py-2">File</th>
                    <th class="text-left px-4 py-2">Status</th>
                    <th class="text-left px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $r->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-2">{{ $r->po_no ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <a class="text-blue-600 underline" href="{{ $r->file_url }}" target="_blank">Lihat PDF</a>
                        </td>
                        <td class="px-4 py-2">
                            <span
                                class="px-2 py-1 rounded text-xs {{ $r->status === 'OK' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $r->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('po.show', $r) }}"
                                    class="px-3 py-1.5 rounded-md border hover:bg-gray-50">Detail</a>

                                <form action="{{ route('po.destroy', $r) }}" method="POST"
                                    onsubmit="return confirm('Hapus data PO ini? Tindakan tidak dapat dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="px-3 py-1.5 rounded-md border border-red-300 text-red-700 hover:bg-red-50">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Belum ada data</td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    <div class="mt-4">
        {{ $rows->links() }}
    </div>
@endsection
