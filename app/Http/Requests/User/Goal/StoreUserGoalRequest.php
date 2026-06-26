<?php

namespace App\Http\Requests\User\Goal;

use App\Http\Requests\ApiBaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserGoalRequest extends ApiBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_clients_count'  => ['required', 'integer', 'min:0'],
            'target_partners_count' => ['required', 'integer', 'min:0'],
            'target_sales_volume'   => ['required', 'integer', 'min:0'],
        ];
    }
}
