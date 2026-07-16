<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Period;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tariff\IndexRequest;
use App\Http\Requests\Admin\Tariff\StoreRequest;
use App\Http\Requests\Admin\Tariff\UpdateRequest;
use App\Http\Resources\TariffResource;
use App\Models\Tariff;
use App\Services\Admin\TariffAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules\Enum;

final class TariffController extends Controller
{
    /**
     * @param TariffAdminService $service
     */
    public function __construct(
        protected TariffAdminService $service
    ) {}

    /**
     * Список тарифов.
     * @param IndexRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        $tariffs = $this->service->list($request->validated());
        return TariffResource::collection($tariffs);
    }

    /**
     * Создание тарифа.
     * @param StoreRequest $request
     * @return TariffResource
     */
    public function store(StoreRequest $request): TariffResource
    {
        $tariff = $this->service->create($request->validated());
        return TariffResource::make($tariff);
    }

    /**
     * Редактирование тарифа.
     * @param UpdateRequest $request
     * @param Tariff $tariff
     * @return TariffResource
     */
    public function update(UpdateRequest $request, Tariff $tariff): TariffResource
    {
        $updatedTariff = $this->service->update($tariff, $request->validated());
        return TariffResource::make($updatedTariff);
    }

    /**
     * Удаление тарифа.
     * @param Tariff $tariff
     * @return Response
     */
    public function destroy(Tariff $tariff): Response
    {
        $this->service->delete($tariff);
        return response()->noContent();
    }
}
