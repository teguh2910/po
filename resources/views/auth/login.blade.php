@extends('layouts.app')

@section('content')
    <div class="max-w-md mx-auto">
        <div class="bg-white shadow rounded-xl p-6">
            <h1 class="text-xl font-semibold mb-4">Login</h1>
            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full border rounded-md px-3 py-2"
                        required>
                    @error('email')
                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm mb-1">Password</label>
                    <input type="password" name="password" class="w-full border rounded-md px-3 py-2" required>
                    @error('password')
                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <button class="w-full py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">Masuk</button>
            </form>
            <p class="text-xs text-gray-500 mt-4">Gunakan akun seeder: admin@example.com / password123</p>
        </div>
    </div>
@endsection
