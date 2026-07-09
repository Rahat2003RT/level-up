<?php

namespace App\Http\Requests\User\Player;

use App\Http\Requests\ApiBaseRequest;
use Carbon\Carbon;

class ShowChecklistRequest extends ApiBaseRequest
{

    public function prepareForValidation(): void
    {
        $this->merge([
            'date' => $this->query('date', Carbon::today()->toDateString()),
        ]);
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date_format:Y-m-d',
        ];
    }
}
