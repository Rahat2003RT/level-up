<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель отладки WebSockets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen p-8">

<div class="max-w-7xl mx-auto grid grid-cols-3 gap-8">

    <!-- Левая колонка: Выбор пользователя -->
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700">
        <h2 class="text-xl font-bold mb-4 text-amber-400">1. Войти как пользователь</h2>
        <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-2">
            @foreach($users as $u)
                <form action="{{ route('debug.sockets.login', $u->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left p-3 rounded bg-gray-700 hover:bg-gray-600 border transition-all
                            {{ auth()->id() === $u->id ? 'border-green-500 bg-gray-600' : 'border-transparent' }}">
                        <div class="font-semibold">{{ $u->name }} {{ $u->surname }}</div>
                        <div class="text-xs text-gray-400">ID: {{ $u->id }} | Role: {{ $u->role?->value }} | Email: {{ $u->email }}</div>
                    </button>
                </form>
            @endforeach
        </div>
    </div>

    <!-- Правая колонка: Статус и Логи сокетов -->
    <div class="col-span-2 space-y-6">
        <!-- Текущий статус -->
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700">
            <h2 class="text-xl font-bold mb-2 text-amber-400">2. Статус авторизации</h2>
            @auth
                <p class="text-green-400">Вы авторизованы как: <span class="font-bold text-white">{{ auth()->user()->name }} (ID: {{ auth()->id() }})</span></p>
            @else
                <p class="text-red-400">Вы не авторизованы. Выберите пользователя слева.</p>
            @endauth
        </div>

        <!-- Консоль сокетов -->
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 flex flex-col h-[50vh]">
            <h2 class="text-xl font-bold mb-2 text-amber-400">3. Лог событий WebSocket (Laravel Echo)</h2>
            <div id="socket-logs" class="bg-black p-4 rounded font-mono text-sm text-green-400 overflow-y-auto flex-1 border border-gray-800 space-y-1">
                <div class="text-gray-500">// Ожидание подключения и событий...</div>
            </div>
        </div>
    </div>

</div>

<!-- Скрипт прослушивания сокетов -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const logsContainer = document.getElementById('socket-logs');

        function log(message, type = 'info') {
            const el = document.createElement('div');
            const colors = {
                info: 'text-blue-400',
                success: 'text-green-400',
                error: 'text-red-400',
                event: 'text-purple-400'
            };
            el.className = colors[type] || 'text-gray-300';
            el.innerText = `[${new Date().toLocaleTimeString()}] ${message}`;
            logsContainer.appendChild(el);
            logsContainer.scrollTop = logsContainer.scrollHeight;
        }

        @auth
        const userId = "{{ auth()->id() }}";
        log(`Инициализация Laravel Echo для пользователя ID: ${userId}...`, 'info');

        // Ждем, пока Laravel Echo станет доступен глобально (из app.js)
        const checkEcho = setInterval(() => {
            if (window.Echo) {
                clearInterval(checkEcho);
                log('Laravel Echo успешно подключен!', 'success');

                // Пример прослушивания приватного канала пользователя (настрой под свой проект)
                window.Echo.private(`user.${userId}`)
                    .listen('.MessageSent', (e) => {
                        log(`Событие MessageSent поймано: ${JSON.stringify(e)}`, 'event');
                    })
                    .error((error) => {
                        log(`Ошибка подключения к приватному каналу: ${JSON.stringify(error)}`, 'error');
                    });
            }
        }, 500);
        @endauth
    });
</script>
</body>
</html>
