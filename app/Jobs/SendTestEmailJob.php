<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTestEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives en cas d'échec
     */
    public int $tries = 3;

    public function __construct(
        public string $recipientEmail,
        public string $subject = 'Test Queue Mailpit',
        public string $body = 'Ceci est un test d\'envoi asynchrone via Excel.'
    ) {}

    public function handle(): void
    {
        Mail::raw($this->body, function ($message) {
            $message->to($this->recipientEmail)
                    ->subject($this->subject);
        });

        Log::info("E-mail de test envoyé avec succès à : {$this->recipientEmail}");
    }
}
