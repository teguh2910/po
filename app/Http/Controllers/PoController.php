<?php

namespace App\Http\Controllers;

use App\Models\PoUpload;
use Illuminate\Http\Request;

class PoController extends Controller
{
    public function index()
    {
        $rows = PoUpload::latest()->paginate(20);

        return view('po.index', compact('rows'));
    }

    public function create()
    {
        return view('po.upload');
    }

    public function store(Request $request, \App\Services\N8nClient $n8n)
    {
        // Validasi: boleh single (pdf) atau multiple (pdfs[])
        $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],         // 50MB
            'pdfs' => ['nullable', 'array', 'max:100'],
            'pdfs.*' => ['file', 'mimes:pdf', 'max:51200'],
        ]);

        // Kumpulkan files
        $files = [];
        if ($request->hasFile('pdfs')) {
            $files = $request->file('pdfs');
        } elseif ($request->hasFile('pdf')) {
            $files = [$request->file('pdf')];
        }

        if (empty($files)) {
            return back()->with('error', 'Tidak ada file yang diunggah.');
        }

        $created = 0;
        $failed = 0;
        $results = [];

        foreach ($files as $file) {
            try {
                // 1) simpan file
                $path = $file->store('po', 'public');
                $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);

                // 2) kirim ke n8n (field "pdf")
                $resp = $n8n->post([], ['pdf' => $file]);

                // pastikan body array
                $body = $resp['body'];
                if (is_string($body)) {
                    $dec = json_decode($body, true);
                    $body = json_last_error() === JSON_ERROR_NONE ? $dec : ['raw' => $body];
                }

                // 3) ambil status & noPo dari respon
                [$ok, $poNo, $extractedSupplierName] = $this->deriveStatusAndPoNo($body);

                // 4) simpan DB
                \App\Models\PoUpload::create([
                    'po_no' => $poNo,
                    'supplier_name' => $extractedSupplierName ?: $request->supplier_name,
                    'file_path' => $path,
                    'file_url' => $url,
                    'status' => $ok ? 'OK' : 'NOT_OK',
                    'n8n_response' => $body,
                    'user_id' => session('user_id'),
                ]);

                $created++;
                $results[] = ['name' => $file->getClientOriginalName(), 'po_no' => $poNo, 'status' => $ok ? 'OK' : 'NOT_OK'];
            } catch (\Throwable $e) {
                $failed++;
                $results[] = ['name' => $file->getClientOriginalName(), 'error' => $e->getMessage()];
            }
        }

        return redirect()
            ->route('po.index')
            ->with('success', "Selesai: {$created} OK, {$failed} gagal.")
            ->with('bulk_results', $results);
    }

    private function deriveStatusAndPoNo(mixed $body): array
    {
        // 1) Jika string, coba decode JSON
        if (is_string($body)) {
            $trim = trim($body);
            $json = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = $json;
            }
        }

        // 2) Ambil array items dari berbagai bentuk (array langsung / {items:[]}/{data:[]})
        $items = [];
        if (is_array($body)) {
            $isAssoc = array_keys($body) !== range(0, count($body) - 1);
            if ($isAssoc) {
                if (isset($body['items']) && is_array($body['items'])) {
                    $items = $body['items'];
                } elseif (isset($body['data']) && is_array($body['data'])) {
                    $items = $body['data'];
                } else {
                    // kalau object tunggal, perlakukan sebagai 1 item
                    $items = [$body];
                }
            } else {
                // sudah array of items
                $items = $body;
            }
        }

        // 3) Aturan OK/NOT_OK berdasarkan field "status"
        //    - OK  : semua item non-summary punya status "match"
        //    - NOT_OK: ada minimal 1 item non-summary statusnya bukan "match"
        $ok = true;
        $poNo = null;
        $supplierName = null;
        $BAD_STATUSES = ['mismatch', 'not_found_in_master', 'invalid_input', 'invalid_price', 'part_mismatch', 'not_ok', 'NOT_OK'];

        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! empty($row['__summary'])) {
                continue;
            } // abaikan summary

            // tangkap nomor PO jika suatu saat dikirim oleh n8n
            if (! $poNo) {
                foreach (['noPo', 'poNo', 'po', 'PO', 'po_number', 'PONumber'] as $k) {
                    if (isset($row[$k]) && is_scalar($row[$k])) {
                        $poNo = trim((string) $row[$k]);
                        break;
                    }
                }
            }

            // tangkap nama supplier dari n8n response
            if (! $supplierName) {
                foreach (['supplier', 'supplier_name', 'vendor', 'vendor_name', 'supplierName', 'vendorName', 'nama_supplier', 'nama_vendor'] as $k) {
                    if (isset($row[$k]) && is_scalar($row[$k])) {
                        $supplierName = trim((string) $row[$k]);
                        break;
                    }
                }
            }

            // baca status per item
            if (isset($row['status'])) {
                $st = strtolower((string) $row['status']);
                if ($st !== 'match') {
                    $ok = false;
                    break;
                }
                // jika mau lebih ketat:
                if (in_array($st, array_map('strtolower', $BAD_STATUSES), true)) {
                    $ok = false;
                    break;
                }
            } else {
                // kalau tidak ada field status sama sekali, anggap NOT_OK (atau ubah sesuai kebijakanmu)
                $ok = false;
                break;
            }
        }

        return [$ok, $poNo, $supplierName];
    }

    public function show(PoUpload $poUpload)
    {
        // n8n_response sudah dicast ke array (lihat model)
        $raw = $poUpload->n8n_response;

        // Siapkan items & summary yang bisa dipakai blade
        [$items, $summary] = $this->normalizeN8nItemsAndSummary($raw);

        return view('po.show', compact('poUpload', 'items', 'summary'));
    }

    private function normalizeN8nItemsAndSummary(mixed $body): array
    {
        // 0) Kalau string, coba decode JSON
        if (is_string($body)) {
            $json = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = $json;
            }
        }

        $arr = [];

        // 1) Bentuk array/object → jadikan array of items
        if (is_array($body)) {
            $isAssoc = array_keys($body) !== range(0, count($body) - 1);

            if ($isAssoc) {
                // Kasus: { items:[...]} atau { data:[...] } atau { json:{...} } (single)
                if (isset($body['items']) && is_array($body['items'])) {
                    $arr = $body['items'];
                } elseif (isset($body['data']) && is_array($body['data'])) {
                    $arr = $body['data'];
                } elseif (isset($body['json']) && is_array($body['json'])) {
                    // single object dengan key json (unwrap)
                    $arr = [$body['json']];
                } else {
                    // single-row assoc
                    $arr = [$body];
                }
            } else {
                // Sudah array numerik
                $arr = $body;

                // 2) Pola khas n8n: setiap elemen punya key 'json' → unwrap
                $hasJsonKey = ! empty($arr) && array_reduce($arr, fn ($c, $x) => $c && is_array($x) && array_key_exists('json', $x), true);
                if ($hasJsonKey) {
                    $arr = array_map(fn ($x) => (is_array($x['json']) ? $x['json'] : $x), $arr);
                }
            }
        } else {
            // bentuk tak dikenal → kosongkan
            $arr = [];
        }

        // 3) Pisahkan items vs summary
        $items = [];
        $summary = null;
        foreach ($arr as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! empty($row['__summary'])) {
                $summary = $summary ?? $row;

                continue;
            }
            $items[] = $row;
        }

        return [$items, $summary];
    }

    public function destroy(PoUpload $poUpload)
    {
        // Hapus file fisik jika ada
        if ($poUpload->file_path) {
            try {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($poUpload->file_path);
            } catch (\Throwable $e) {
                // opsional: log error
                \Log::warning('Gagal hapus file PO: '.$poUpload->file_path.' | '.$e->getMessage());
            }
        }

        // Hapus record DB
        $poUpload->delete();

        return redirect()
            ->route('po.index')
            ->with('success', 'Data PO berhasil dihapus.');
    }
}
