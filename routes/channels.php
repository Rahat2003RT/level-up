<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'middleware' => ['auth:sanctum']
]);

Broadcast::channel('chat.{id}', function ($user, $id) {
    $chat = Chat::find($id);

    if (!$chat) {
        return false;
    }

    $isParticipant = (int) $user->id === $chat->elite_id || (int) $user->id === $chat->leader_id;

    if ($isParticipant) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'role' => $user->role?->name,
        ];
    }

    return false;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
