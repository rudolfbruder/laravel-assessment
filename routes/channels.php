<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Any authenticated user may listen for comment activity on a task,
// matching the open comment/view access model (future project-manager roles).
Broadcast::channel('tasks.{taskId}.comments', function ($user) {
    return $user !== null;
});

// Shared channel carrying app-wide new-comment notifications.
Broadcast::channel('comments.notifications', function ($user) {
    return $user !== null;
});
