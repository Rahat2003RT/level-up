<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель отладки WebSockets</title>

    <!-- Подключаем Tailwind CSS через CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Подключаем библиотеки сокетов напрямую через CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

    <script>
        // Вручную поднимаем экземпляр Echo для работы с Laravel Reverb
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ config("broadcaster.connections.reverb.key") ?? "laravel-reverb-key" }}',
            wsHost: window.location.hostname,
            wsPort: 8080, // Проверь порт запуска твоего Reverb (обычно 8080)
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

    <!-- Правая колонка: Управление и Логи -->
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
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-amber-400">3. Тестирование чатов</h2>
                @auth
                    <button type="button" id="btn-load-chats" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition-all">
                        🔄 Показать мои чаты
                    </button>
                @endauth
            </div>

            @auth
                <!-- Контейнер для динамического списка доступных чатов -->
                <div id="available-chats-list" class="hidden mb-4 p-3 bg-gray-900 rounded border border-gray-700 text-sm max-h-40 overflow-y-auto space-y-1">
                    <div class="text-gray-400 text-xs">// Запрос чатов...</div>
                </div>

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

<!-- Интерактивный JS скрипт -->
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

            // Функция динамического переподключения к приватному каналу чата
            function subscribeToChat(chatId) {
                window.Echo.leave(`chat.${chatId}`);

                log(`Подписываемся на приватный канал: chat.${chatId}`, 'info');

                window.Echo.private(`chat.${chatId}`)
                    // Если событие в бэкенде называется по-другому (без broadcastAs), используй полное имя класса: '.App\\Events\\MessageSent'
                    .listen('.MessageSent', (e) => {
                        log(`🔥 Поймано событие MessageSent: ${JSON.stringify(e.message || e)}`, 'event');
                    })
                    .error((error) => {
                        log(`❌ Ошибка приватного канала: ${JSON.stringify(error)}`, 'error');
                    });
            }

            // Подписка при первой загрузке страницы
            subscribeToChat(chatIdInput.value);

            // Смена подписки при ручном изменении ID чата в инпуте
            chatIdInput.addEventListener('change', (e) => {
                subscribeToChat(e.target.value);
            });

            // Функция запроса списка доступных чатов текущего юзера
            document.getElementById('btn-load-chats').addEventListener('click', async () => {
                const chatsContainer = document.getElementById('available-chats-list');
                chatsContainer.classList.remove('hidden');
                chatsContainer.innerHTML = '<div class="text-gray-400 text-xs">Запрашиваю список чатов из API...</div>';

                try {
                    const response = await fetch('/api/v1/chats', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    const result = await response.json();
                    const chats = result.data || result;

                    if (response.ok && Array.isArray(chats)) {
                        if (chats.length === 0) {
                            chatsContainer.innerHTML = '<div class="text-amber-400 text-xs">У этого пользователя пока нет активных чатов в БД.</div>';
                            return;
                        }

                        chatsContainer.innerHTML = '<div class="text-xs text-gray-400 mb-2">// Кликни на чат, чтобы выбрать его ID:</div>';

                        chats.forEach(chat => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'block w-full text-left p-1.5 rounded bg-gray-800 hover:bg-gray-700 text-xs font-mono transition-all border border-gray-700 hover:border-amber-400';

                            const previewText = chat.last_message?.text || 'нет сообщений';
                            btn.innerText = `[Чат #${chat.id}] Собеседник: ${chat.role || 'Диалог'} | Текст: "${previewText}"`;

                            btn.addEventListener('click', () => {
                                chatIdInput.value = chat.id;
                                chatIdInput.dispatchEvent(new Event('change'));
                                log(`Выбран чат #${chat.id} из списка доступных`, 'info');
                            });

                            chatsContainer.appendChild(btn);
                        });
                    } else {
                        chatsContainer.innerHTML = `<div class="text-red-400 text-xs">Ошибка API: ${JSON.stringify(result)}</div>`;
                    }
                } catch (err) {
                    chatsContainer.innerHTML = `<div class="text-red-400 text-xs">Сбой запроса: ${err.message}</div>`;
                }
            });

            // Обработчик отправки сообщения (имитируем запрос от мобильного приложения)
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
