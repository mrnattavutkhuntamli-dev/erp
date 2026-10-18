<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Mini ERP') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <!-- เพิ่ม bg-gray-100 เพื่อให้พื้นหลังเป็นสีเทาอ่อน -->
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">

            <div class="mb-6">
                <a href="/">
                    <h1 class="text-4xl font-extrabold text-indigo-600">🚀 Mini ERP</h1>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-8 py-10 bg-white shadow-xl overflow-hidden sm:rounded-2xl border border-gray-100">
                {{ $slot }}
            </div>

            <div class="mt-8 text-sm text-gray-500">
                &copy; {{ date('Y') }} Mini ERP System. All rights reserved.
            </div>
        </div>
    </body>
</html>
