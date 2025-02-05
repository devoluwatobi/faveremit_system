<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class rejectedWithdraw extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;
    public $status;
    public $reason;
    public $time;
    public $balance;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->name = $data['name'];
        $this->amount = $data['amount'];
        $this->reason = $data['reason'];
        $this->status = $data['status'];
        $this->balance = $data['balance'];
        $this->time = $data['time'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your withdrawal has been rejected  | Faveremit')
            ->view('emails.rejectedWithdraw');
    }
}
