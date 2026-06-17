<?php

namespace App\Http\Controllers\Api\v1\Server;

use App\Http\Controllers\Controller;
use App\Http\Requests\CityIndexRequest;
use App\Http\Requests\CountryIndexRequest;
use App\Http\Resources\CityResource;
use App\Http\Resources\CountryResource;
use App\Services\Server\RegionService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Регионы', weight: 0)]
class RegionController extends Controller
{
    public function __construct(protected RegionService $service)
    {

    }

    /**
     * @param CountryIndexRequest $request
     * @return AnonymousResourceCollection
     */
    public function getCountries(CountryIndexRequest $request): AnonymousResourceCollection
    {
        $countries = $this->service->getCountries($request->validated());
        return CountryResource::collection($countries);
    }

    /**
     * @param CityIndexRequest $request
     * @return AnonymousResourceCollection
     */
    public function getCities(CityIndexRequest $request): AnonymousResourceCollection
    {
        $cities = $this->service->getCities($request->validated());
        return CityResource::collection($cities);
    }
}
