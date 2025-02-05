<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class buyPower extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;
    public $service;
    public $number;
    public $trxID;
    public $time;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $amount, $number, $service, $trxID, $time)
    {
        $this->name = $user->first_name;
        $this->amount = $amount;
        $this->number = $number;
        $this->service = $service;
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
        return $this->subject('Electricity purchase received | Faveremit')
            ->view('emails.electricity');
    }
}
