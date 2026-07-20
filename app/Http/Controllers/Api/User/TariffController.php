<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\TariffResource;
use App\Services\User\TariffService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Тарифы', weight: 290)]
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
     * Все тарифы
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tariffs = $this->service->getTariffs($request->user());
        return TariffResource::collection($tariffs);
    }

    /**
     * Выбрать тариф.
     */
    public function selectTariff(Request $request, int $tariffId): Response
    {
        $this->service->selectTariff($request->user(), $tariffId);
        return response()->noContent();
    }

    /**
     * Отменить автопродление/подписку на текущий тариф.
     */
    public function cancelSubscription(Request $request): Response
    {
        $this->service->cancelAutoRenew($request->user());
        return response()->noContent();
    }
}
