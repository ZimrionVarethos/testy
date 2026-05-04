<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class BrevoChannel
{
    public function send($notifiable, Notification $notification): void
    {
        $notification->toBrevo($notifiable);
    }
}