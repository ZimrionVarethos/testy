<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }

    public function toMail($notifiable): void
    {
        $url = $this->verificationUrl($notifiable);

        Http::withHeaders([
            'api-key' => env('BREVO_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => config('app.name'),
                'email' => env('MAIL_FROM_ADDRESS'),
            ],
            'to' => [['email' => $notifiable->email]],
            'subject' => 'Verify Your Email Address',
            'htmlContent' => "
                <h2>Verify Your Email</h2>
                <p>Click the button below to verify your email address.</p>
                <a href='{$url}' style='background:#4F46E5;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;display:inline-block;'>
                    Verify Email
                </a>
                <p>Link expires in 60 minutes.</p>
                <p>If you did not register, ignore this email.</p>
            ",
        ]);
    }
}