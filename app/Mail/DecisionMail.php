<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class DecisionMail extends Mailable
{
    public $status;

    public function __construct($status)
    {
        $this->status = $status;
    }

    public function build(): DecisionMail
    {
        return $this->subject('Résultat de traitement')
            ->view('emails.decision')
            ->with(['status' => $this->status]);
    }
}
