<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Dashboard - {{ strtoupper($systemInfo['app_env']) }}</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-900 text-slate-100 font-sans min-h-screen flex items-center justify-center p-6">

<div class="max-w-4xl w-full bg-slate-800 rounded-xl shadow-2xl border border-slate-700 p-8">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-slate-700 pb-6 mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-white">System Monitor</h1>
            <p class="text-slate-400 mt-1">Текущий статус Docker-окружения для проекта <span class="text-indigo-400 font-semibold">level-up</span></p>
        </div>

        @if($systemInfo['app_env'] === 'production')
            <span class="px-5 py-2 rounded-full text-sm font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 animate-pulse">
                    🚀 PRODUCTION MODE
                </span>
        @else
            <span class="px-5 py-2 rounded-full text-sm font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                    💻 LOCAL DEVELOPMENT
                </span>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="bg-slate-800/50 p-5 rounded-lg border border-slate-700/60">
            <h3 class="text-lg font-semibold text-slate-300 mb-4 flex items-center gap-2">
                ℹ️ Основная информация
            </h3>
            <ul class="space-y-3 text-sm">
                <li class="flex justify-between"><span class="text-slate-400">Имя хоста (Контейнер):</span> <span class="font-mono text-indigo-300">{{ $systemInfo['hostname'] }}</span></li>
                <li class="flex justify-between"><span class="text-slate-400">ОС Сервера:</span> <span class="text-slate-200">{{ $systemInfo['server_os'] }}</span></li>
                <li class="flex justify-between"><span class="text-slate-400">Версия PHP:</span> <span class="font-mono text-slate-200">{{ $systemInfo['php_version'] }}</span></li>
                <li class="flex justify-between"><span class="text-slate-400">Версия Laravel:</span> <span class="font-mono text-slate-200">{{ $systemInfo['laravel_ver'] }}</span></li>
                <li class="flex justify-between"><span class="text-slate-400">Режим отладки (Debug):</span> <span class="font-semibold">{{ $systemInfo['app_debug'] }}</span></li>
            </ul>
        </div>

        <div class="bg-slate-800/50 p-5 rounded-lg border border-slate-700/60">
            <h3 class="text-lg font-semibold text-slate-300 mb-4 flex items-center gap-2">
                🔄 Статус Docker-сервисов
            </h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-400 font-medium">PostgreSQL (postgres)</span>
                        <span class="font-semibold text-xs">{{ $dbStatus }}</span>
                    </div>
                    <p class="text-xs text-slate-500 font-mono truncate">{{ $dbVersion }}</p>
                </div>

                <div class="border-t border-slate-700/40 pt-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400 font-medium">Redis Cache & Queue (redis)</span>
                        <span class="font-semibold text-xs">{{ $redisStatus }}</span>
                    </div>
                </div>

                <div class="border-t border-slate-700/40 pt-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-400 font-medium">Laravel Reverb (reverb)</span>
                        <span class="font-semibold text-xs">{{ $reverbStatus }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-8 pt-4 border-t border-slate-700/60 text-center text-xs text-slate-500 flex justify-between items-center">
        <span>Контейнер сборки: PHP-FPM Alpine</span>
        <span>{{ now()->format('Y-m-d H:i:s') }}</span>
    </div>

</div>

</body>
</html>
