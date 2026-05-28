<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['brevo']; // custom channel, bukan 'mail'
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'api.auth.verify-email',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }

    public function toBrevo($notifiable): void
    {
        $url = $this->verificationUrl($notifiable);

        $apiKey = config('services.brevo.key') ?: env('BREVO_API_KEY');
        $fromEmail = config('mail.from.address') ?: env('MAIL_FROM_ADDRESS');
        $fromName = config('mail.from.name') ?: config('app.name');

        if (!$apiKey || !$fromEmail) {
            Log::warning('Brevo email skipped because configuration is incomplete.', [
                'api_key_set' => !empty($apiKey),
                'from_email_set' => !empty($fromEmail),
                'to_email' => $notifiable->email,
            ]);

            return;
        }

        $response = Http::timeout(15)->withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => $fromName,
                'email' => $fromEmail,
            ],
            'to' => [[
                'email' => $notifiable->email,
                'name' => $notifiable->name,
            ]],
            'subject' => 'Verifikasi Email Bening Rental',
            'htmlContent' => "
                <h2>Verifikasi Email</h2>
                <p>Halo {$notifiable->name}, klik tombol di bawah untuk memverifikasi email akun Bening Rental Anda.</p>
                <a href='{$url}' style='background:#111827;color:white;padding:12px 24px;text-decoration:none;display:inline-block;font-weight:700;'>
                    Verifikasi Email
                </a>
                <p>Link berlaku selama 60 menit.</p>
            ",
        ]);

        $logContext = [
            'status' => $response->status(),
            'body' => $response->body(),
            'api_key_set' => true,
            'from_email' => $fromEmail,
            'to_email' => $notifiable->email,
        ];

        if ($response->successful()) {
            Log::info('Brevo verification email sent.', $logContext);
            return;
        }

        Log::warning('Brevo verification email failed.', $logContext);
    }
}
