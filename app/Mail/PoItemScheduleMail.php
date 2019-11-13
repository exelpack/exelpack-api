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
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($schedules,$subj)
    {
        $this->schedules = $schedules;
        $this->subj = $subj;
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
                'schedules' => $this->schedules
            ]);
    }
}
