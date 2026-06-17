<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $systemInfo = [
            'app_env'      => config('app.env'),
            'app_debug'    => config('app.debug') ? 'Включен 🔴' : 'Выключен 🟢',
            'php_version'  => PHP_VERSION,
            'laravel_ver'  => app()->version(),
            'server_os'    => PHP_OS_FAMILY,
            'hostname'     => gethostname(),
        ];

        $dbStatus = '🟢 Подключено';
        $dbVersion = 'Неизвестно';
        try {
            $pdo = DB::connection()->getPdo();
            $dbVersion = DB::select('SELECT version()')[0]->version ?? 'PostgreSQL';
        } catch (\Exception $e) {
            $dbStatus = '🔴 Ошибка: ' . $e->getMessage();
        }

        $redisStatus = '🟢 Подключено';
        try {
            Redis::ping();
        } catch (\Exception $e) {
            $redisStatus = '🔴 Ошибка: ' . $e->getMessage();
        }

        $reverbHost = config('broadcasting.connections.reverb.options.host', '127.0.0.1');

        if ($systemInfo['app_env'] === 'local' && $reverbHost === '127.0.0.1') {
            $reverbHost = 'reverb';
        }

        $reverbPort = 8080;
        $reverbStatus = '🔴 Отключен';

        $connection = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 2);
        if (is_resource($connection)) {
            $reverbStatus = "🟢 Запущен и принимает соединения ($reverbHost:$reverbPort)";
            fclose($connection);
        } else {
            $fallbackConnection = @fsockopen('127.0.0.1', $reverbPort, $errno, $errstr, 2);
            if (is_resource($fallbackConnection)) {
                $reverbStatus = "🟢 Запущен локально (127.0.0.1:$reverbPort)";
                fclose($fallbackConnection);
            } else {
                $reverbStatus = "🔴 Недоступен ($errstr)";
            }
        }

        return view('welcome', compact('systemInfo', 'dbStatus', 'dbVersion', 'redisStatus', 'reverbStatus'));
    }
}
