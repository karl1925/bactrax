<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\{User};

Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->getKey() === $userId;
});

Broadcast::channel('office.{officeId}', function (User $user, int $officeId) {
    return $user->offices()->whereKey($officeId)->exists();
});
