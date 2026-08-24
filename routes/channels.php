<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

use App\Models\User;

Broadcast::channel('office.{officeId}', function (User $user, int $officeId) {
    return $user->offices->pluck('id')->contains($officeId)
        || $user->roles()->whereIn('name', ['admin', 'bac_secretariat', 'hope'])->exists();
});
