<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PoApprovalNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $poDetails;
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
        return $this->view('mail.poApprovalNotification')
            ->subject("NEW PURCHASE ORDER FOR APPROVAL ".$this->poDetails->poNumber)
            ->with([
                'poDetails' => $this->poDetails
            ]);
    }
}
