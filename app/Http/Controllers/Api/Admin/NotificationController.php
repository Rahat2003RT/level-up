<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notifications\SendMassPushRequest;
use App\Services\Admin\NotificationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

#[Group('Уведомления / Админка', weight: 0)]
final class  NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $service
    ) {}

    /**
     * Массовая рассылка уведомлений всем пользователям
     * @param SendMassPushRequest $request
     * @return Response
     */
    public function sendToAll(SendMassPushRequest $request): Response
    {
        $this->service->sendMassPush($request->validated());
        return response()->noContent();
    }
}
