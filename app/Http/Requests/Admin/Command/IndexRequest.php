<?php

namespace App\Http\Requests\Admin\Command;

use App\Http\Requests\ApiBaseRequest;

class IndexRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'role'  => 'required|in:leader,elite',
            'limit' => 'integer|between:1,100',
            'page'  => 'integer|between:1,100',
            'query' => 'sometimes|string|max:255',
        ];
    }
}
