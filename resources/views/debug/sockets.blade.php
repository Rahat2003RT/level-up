<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель отладки WebSockets</title>

    <!-- Подключаем Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Подключаем клиентские библиотеки сокетов напрямую через CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

    <script>
        // Вручную поднимаем экземпляр Echo
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ config("broadcaster.connections.reverb.key") ?? "laravel-reverb-key" }}',
            wsHost: window.location.hostname,
            wsPort: 8080, // Проверь, чтобы порт совпадал с тем, на котором крутится твой Reverb
            forceTLS: false,
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
        });
    </script>
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
                        <div class="text-xs text-gray-400">ID: {{ $u->id }} | Роль: {{ $u->role?->value ?? $u->role }} | {{ $u->email }}</div>
                    </button>
                </form>
            @endforeach
        </div>
    </div>

    <!-- Правая колонка: Статус, Форма отправки и Логи -->
    <div class="col-span-2 space-y-6 flex flex-col h-[85vh]">

        <!-- Текущий статус -->
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 shrink-0">
            <h2 class="text-xl font-bold mb-2 text-amber-400">2. Статус авторизации</h2>
            @auth
                <p class="text-green-400">Вы авторизованы как: <span class="font-bold text-white">{{ auth()->user()->name }} (ID: {{ auth()->id() }})</span></p>
            @else
                <p class="text-red-400">Вы не авторизованы. Выберите пользователя слева.</p>
            @endauth
        </div>

        <!-- Блок отправки тестового сообщения -->
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 shrink-0">
            <h2 class="text-xl font-bold mb-4 text-amber-400">3. Отправить тестовое сообщение в чат</h2>
            @auth
                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">ID Чата</label>
                        <input type="number" id="test-chat-id" value="1" class="w-full p-2 rounded bg-gray-700 border border-gray-600 font-mono text-white focus:outline-none focus:border-amber-400">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-400 mb-1">Текст сообщения</label>
                        <input type="text" id="test-message-text" value="Привет из песочницы сокетов!" class="w-full p-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:border-amber-400">
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="btn-send-message" class="w-full p-2 bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold rounded transition-all">
                            Отправить
                        </button>
                    </div>
                </div>
            @else
                <p class="text-gray-500 text-sm">Форма станет доступна после выбора пользователя.</p>
            @endauth
        </div>

        <!-- Консоль сокетов -->
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg border border-gray-700 flex flex-col flex-1 min-h-0">
            <h2 class="text-xl font-bold mb-2 text-amber-400">4. Лог событий WebSocket (Laravel Echo)</h2>
            <div id="socket-logs" class="bg-black p-4 rounded font-mono text-sm overflow-y-auto flex-1 border border-gray-800 space-y-1">
                <div class="text-gray-500">// Ожидание подключения и событий...</div>
            </div>
        </div>
    </div>

</div>

<!-- Скрипт прослушивания сокетов и отправки API запросов -->
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

        if (window.Echo) {
            log('Laravel Echo успешно подключен к Reverb!', 'success');

            const chatIdInput = document.getElementById('test-chat-id');

            function subscribeToChat(chatId) {
                // Покидаем старый канал перед подпиской на новый
                window.Echo.leave(`chat.${chatId}`);

                log(`Подписываемся на приватный канал: chat.${chatId}`, 'info');

                // Обрати внимание: если у тебя в PHP канале чата указано 'chats.{id}',
                // то здесь нужно поменять 'chat.' на 'chats.'
                window.Echo.private(`chat.${chatId}`)
                    // Обрати внимание: слушаем '.MessageSent'. Если не работает, попробуй полное имя: '.App\\Events\\MessageSent'
                    .listen('.MessageSent', (e) => {
                        log(`🔥 Поймано событие MessageSent: ${JSON.stringify(e.message || e)}`, 'event');
                    })
                    .error((error) => {
                        log(`❌ Ошибка приватного канала: ${JSON.stringify(error)}`, 'error');
                    });
            }

            // Слушаем чат, указанный в инпуте при загрузке страницы
            subscribeToChat(chatIdInput.value);

            // Меняем прослушиваемый канал, если ввели другой ID
            chatIdInput.addEventListener('change', (e) => {
                subscribeToChat(e.target.value);
            });

            // Обработчик кнопки "Отправить"
            document.getElementById('btn-send-message').addEventListener('click', async () => {
                const chatId = chatIdInput.value;
                const text = document.getElementById('test-message-text').value;

                log(`Отправка POST запроса на API для чата #${chatId}...`, 'info');

                try {
                    const response = await fetch(`/api/v1/chats/${chatId}/messages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ text: text })
                    });

                    const result = await response.json();

                    if (response.ok) {
                        log(`✅ Сообщение успешно создано в БД! ID: ${result.data?.id ?? 'неизвестно'}`, 'success');
                    } else {
                        log(`❌ Ошибка API (${response.status}): ${JSON.stringify(result)}`, 'error');
                    }
                } catch (err) {
                    log(`❌ Сбой сетевого запроса: ${err.message}`, 'error');
                }
            });
        } else {
            log('❌ Ошибка: Экземпляр window.Echo не найден!', 'error');
        }
        @endauth
    });
</script>
</body>
</html>
