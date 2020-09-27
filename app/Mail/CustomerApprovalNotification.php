<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerApprovalNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $customer;
    private $approver;
    public function __construct($customer, $approver)
    {
        $this->customer = $customer;
        $this->approver = $approver;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.customerApprovalNotification')
            ->subject("NEW CUSTOMER FOR APPROVAL")
            ->with([    
                'customer' => $this->customer,
                'approver' => $this->approver,
            ]);
    }
}
