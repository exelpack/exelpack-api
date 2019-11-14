<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PoItemScheduleMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $schedules;
    protected $subj;
    protected $sender;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($schedules,$subj,$sender)
    {
        $this->schedules = $schedules;
        $this->subj = $subj;
        $this->sender = $sender;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.PoItemScheduleMail')
            ->subject($this->subj)
            ->with([    
                'schedules' => $this->schedules,
                'sender' => $this->sender
            ]);
    }
}
