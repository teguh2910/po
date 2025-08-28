@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto bg-white rounded-xl shadow p-6">
        <h1 class="text-lg font-semibold mb-4">Upload PDF Purchase Order</h1>

        <form action="{{ route('po.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm mb-1">File PDF</label>
                <!-- ganti input file lama -->
                <input type="file" name="pdfs[]" accept="application/pdf" multiple required class="block w-full">
                <p class="text-xs text-gray-500 mt-1">Pilih sampai 100 PDF.</p>

                @error('pdf')
                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                @enderror
                <p class="text-xs text-gray-500 mt-1">Maks 30MB</p>
            </div>



            <button class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">Upload & Proses</button>
        </form>
    </div>
@endsection
