<?php

namespace App\Http\Requests\User\Goal;

use App\Http\Requests\ApiBaseRequest;

class StoreUserGoalRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'target_clients_count' => ['required', 'integer', 'min:0'],
            'target_partners_count' => ['required', 'integer', 'min:0'],
            'target_sales_volume' => ['required', 'integer', 'min:0'],
        ];
    }
}
