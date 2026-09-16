<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Channel authorization for client private channels.
 * Validates that authenticated Client has matching id.
 */
Broadcast::channel('client.{id}', function ($client, $id) {
    if (!$client) {
        return false;
    }
    return (string) $client->id === (string) $id && $client->status === 'active';
});
