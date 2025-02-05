<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class buyDataBundle extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;
    public $number;
    public $package;
    public $trxID;
    public $time;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $amount, $number, $package, $trxID, $time)
    {
        $this->name = $user->first_name;
        $this->amount = $amount;
        $this->number = $number;
        $this->package = $package;
        $this->trxID = $trxID;
        $this->time = $time;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Data bundle purchase | Faveremit')
            ->view('emails.data');
    }
}
