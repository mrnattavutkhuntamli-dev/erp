<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันตัวตน 2FA | Mini ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'IBM Plex Sans Thai', sans-serif; } </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full">
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">ยืนยันตัวตน 2FA</h1>
            <p class="text-slate-500 mt-2 text-sm">กรุณากรอกรหัส 6 หลักจากแอป Google Authenticator บนมือถือของคุณ</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-3 mb-4 rounded-lg text-sm text-center">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('2fa.verify') }}" method="POST">
            @csrf
            <input type="text" name="otp" required maxlength="6" placeholder="000000"
                   class="w-full border p-4 rounded-xl text-center text-3xl tracking-[0.5em] font-bold mb-6 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                   autofocus autocomplete="off">

            <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold text-lg hover:bg-indigo-700 transition shadow-lg">
                ยืนยันรหัส
            </button>
        </form>

        <div class="mt-6 text-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-slate-400 hover:text-red-500">ยกเลิกและออกจากระบบ</button>
            </form>
        </div>
    </div>
</body>
</html>
