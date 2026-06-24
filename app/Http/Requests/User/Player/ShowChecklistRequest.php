<?php

namespace App\Http\Requests\User\Player;

use App\Http\Requests\ApiBaseRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowChecklistRequest extends ApiBaseRequest
{

    public function prepareForValidation(): void
    {
        $this->merge([
            'date' => $this->query('date', Carbon::today()->toDateString()),
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date_format:Y-m-d',
        ];
    }
}
