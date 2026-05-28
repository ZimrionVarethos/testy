<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['brevo'];
    }

    public function toBrevo($notifiable): void
    {
        $resetUrl = URL::route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $apiKey = config('services.brevo.key') ?: env('BREVO_API_KEY');
        $fromEmail = config('mail.from.address') ?: env('MAIL_FROM_ADDRESS');
        $fromName = config('mail.from.name') ?: config('app.name');
        $expire = config('auth.passwords.users.expire', 60);

        if (!$apiKey || !$fromEmail) {
            Log::warning('Brevo reset password email skipped because configuration is incomplete.', [
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
            'subject' => 'Reset Password Bening Rental',
            'htmlContent' => "
                <h2>Reset Password</h2>
                <p>Halo {$notifiable->name}, klik tombol di bawah untuk membuat password baru akun Bening Rental Anda.</p>
                <a href='{$resetUrl}' style='background:#111827;color:white;padding:12px 24px;text-decoration:none;display:inline-block;font-weight:700;'>
                    Reset Password
                </a>
                <p>Link berlaku selama {$expire} menit. Abaikan email ini jika Anda tidak meminta reset password.</p>
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
            Log::info('Brevo reset password email sent.', $logContext);
            return;
        }

        Log::warning('Brevo reset password email failed.', $logContext);
    }
}
