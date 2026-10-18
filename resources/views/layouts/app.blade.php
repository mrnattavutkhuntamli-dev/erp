<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini ERP - @yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex-shrink-0 flex flex-col">
        <div class="h-16 flex items-center justify-center text-xl font-bold border-b border-gray-800">
            🚀 Mini ERP
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="{{ route('dashboard') }}" class="block px-4 py-2 rounded hover:bg-gray-800 {{ request()->routeIs('dashboard') ? 'bg-gray-800 font-semibold' : '' }}">📊 Dashboard</a>
            <a href="{{ route('products.index') }}" class="block px-4 py-2 rounded hover:bg-gray-800 {{ request()->routeIs('products.*') ? 'bg-gray-800 font-semibold' : '' }}">📦 สินค้า</a>
            <a href="{{ route('categories.index') }}" class="block px-4 py-2 rounded hover:bg-gray-800 {{ request()->routeIs('categories.*') ? 'bg-gray-800 font-semibold' : '' }}">📑 หมวดหมู่</a>
            <a href="{{ route('suppliers.index') }}" class="block px-4 py-2 rounded hover:bg-gray-800 {{ request()->routeIs('suppliers.*') ? 'bg-gray-800 font-semibold' : '' }}">🏢 ซัพพลายเออร์</a>
            <a href="{{ route('units.index') }}" class="block px-4 py-2 rounded hover:bg-gray-800 {{ request()->routeIs('units.*') ? 'bg-gray-800 font-semibold' : '' }}">⚖️ หน่วยนับ</a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <div class="text-sm text-gray-400 mb-2">ล็อกอินในชื่อ: <br><span class="text-white">{{ Auth::user()->name }}</span></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded text-sm">ออกจากระบบ</button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="h-16 bg-white shadow flex items-center px-6">
            <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
        </header>

        <!-- Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            @yield('content')
        </main>
    </div>

</body>
</html>
