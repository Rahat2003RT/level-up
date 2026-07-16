<?php

namespace App\Http\Requests\Admin\Tariff;

use App\Http\Requests\ApiBaseRequest;

class IndexRequest extends ApiBaseRequest
{

    public function rules(): array
    {
        return [
            'order_by' => 'nullable|string|in:id,role,price,period,is_active',
            'order_sort' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
