<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class buyAirtime extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;
    public $number;
    public $trxID;
    public $time;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $amount, $number, $trxID, $time)
    {
        $this->name = $user->first_name;
        $this->amount = $amount;
        $this->number = $number;
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
        return $this->subject('Airtime purchase | Faveremit')
            ->view('emails.airtime');
    }
}
