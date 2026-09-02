<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\{User};

Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return true;
});

Broadcast::channel('office.{officeId}', function (User $user, int $officeId) {
    return true;
});
