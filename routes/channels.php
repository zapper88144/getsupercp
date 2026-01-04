<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('monitoring', function ($user) {
    return $user->is_admin;
});

Broadcast::channel('logs.{type}', function ($user, $type) {
    return $user->is_admin;
});
