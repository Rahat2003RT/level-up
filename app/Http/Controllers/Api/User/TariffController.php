<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\TariffResource;
use App\Services\User\TariffService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TariffController extends Controller
{
    /**
     * @param TariffService $service
     */
    public function __construct(
        protected TariffService $service
    ) {
    }

    /**
     * Список тарифов, доступных для текущей роли пользователя.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tariffs = $this->service->getTariffs($request->user());
        return TariffResource::collection($tariffs);
    }
}
