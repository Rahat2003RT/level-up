<?php

declare(strict_types=1);

namespace App\Http\Requests\User\Chat;

use App\Http\Requests\ApiBaseRequest;
use App\Models\Chat;

final class StoreMessageRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        /** @var Chat|null $chat */
        $chat = $this->route('chat');

        if (!$chat) {
            return false;
        }

        return (int) $this->user()->id === $chat->elite_id
            || (int) $this->user()->id === $chat->leader_id;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:3000'],
        ];
    }
}
