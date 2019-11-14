<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PoEmailNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $poDetails;

    public function __construct($poDetails)
    {
        $this->poDetails = $poDetails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.poEmailNotification')
            ->subject("NEW PURCHASE ORDER FROM ".$this->poDetails['customer'])
            ->with([
                'poDetails' => $this->poDetails
            ]);
    }
}
