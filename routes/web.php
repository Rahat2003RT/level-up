<?php

use App\Http\Controllers\DashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class);
Route::get('/test-forgot', function () {
    return '
        <div style="max-width:400px; margin:50px auto; font-family:sans-serif;">
            <h2>Тест: Забыл пароль</h2>
            <form action="/api/v1/forgot-password" method="POST">
                <input type="hidden" name="lang" value="ru">
                <p>Email (должен быть rturmyshov@gmail.com):</p>
                <input type="email" name="email" value="rturmyshov@gmail.com" required style="width:100%; padding:8px; margin-bottom:15px;">
                <button type="submit" style="padding:10px 20px; cursor:pointer;">Отправить письмо через Resend</button>
            </form>
        </div>
    ';
});

// 2. Страница ввода нового пароля (куда ведет ссылка)
// Laravel по умолчанию ожидает роут с именем 'password.reset' для генерации ссылок
Route::get('/password-reset', function (\Illuminate\Http\Request $request) {
    return '
        <div style="max-width:400px; margin:50px auto; font-family:sans-serif;">
            <h2>Тест: Придумайте новый пароль</h2>
            <form action="/api/v1/reset-password" method="POST">
                <input type="hidden" name="token" value="' . e($request->query('token')) . '">
                <input type="hidden" name="email" value="' . e($request->query('email')) . '">

                <p>Новый пароль:</p>
                <input type="password" name="password" required style="width:100%; padding:8px; margin-bottom:10px;">

                <p>Повторите пароль:</p>
                <input type="password" name="password_confirmation" required style="width:100%; padding:8px; margin-bottom:15px;">

                <button type="submit" style="padding:10px 20px; cursor:pointer;">Сохранить новый пароль</button>
            </form>
        </div>
    ';
})->name('password.reset');



Route::get('/debug/sockets-sandbox', function () {
    $users = User::query()->latest()->limit(15)->get();
    // Забираем токен из сессии флеш-данных, если он там есть
    $sanctumToken = session('sanctum_token');
    return view('debug.sockets', compact('users', 'sanctumToken'));
})->name('debug.sockets');

Route::post('/debug/sockets-sandbox/login/{user}', function (User $user) {
    Auth::login($user);

    // Генерируем Sanctum-токен для этого пользователя
    $tokenResult = $user->createToken('debug-token');

    // Кладем plainTextToken в сессию, чтобы забрать на странице редиректа
    return redirect()->route('debug.sockets')->with('sanctum_token', $tokenResult->plainTextToken);
})->name('debug.sockets.login');
