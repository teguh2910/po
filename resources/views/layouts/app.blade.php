<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'PO × n8n' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white border-b sticky top-0 z-10">
        <div class="mx-auto max-w-6xl px-4 py-3 flex items-center justify-between">
            <a href="{{ route('po.index') }}" class="font-semibold">PO Monitor</a>
            <div class="flex items-center gap-4 text-sm">
                @if (session('user_id'))
                    <span>Hi, {{ session('user_name') }}</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="px-3 py-1.5 rounded-md bg-gray-800 text-white hover:bg-black">Logout</button>
                    </form>
                @endif
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if (session('error'))
            <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="mb-4 p-3 rounded-md bg-green-100 text-green-800">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="text-center text-xs text-gray-500 py-8">
        &copy; {{ date('Y') }} — Laravel 12 × n8n
    </footer>
</body>

</html>
