<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PrApprovalNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $prDetails;
    public function __construct($prDetails)
    {
        $this->prDetails = $prDetails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.prApprovalNotification')
            ->subject("NEW PURCHASE REQUEST FOR APPROVAL ".$this->prDetails->prNumber)
            ->with([
                'prDetails' => $this->prDetails
            ]);
    }
}
