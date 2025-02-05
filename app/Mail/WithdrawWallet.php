<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WithdrawWallet extends Mailable
{
    use Queueable, SerializesModels;
    public $email;
    public $amount;
    public $name;
    public $balance;
    public $time;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $amount, $balance, $time)
    {
        $this->email = $user->email;
        $this->name = $user->first_name;
        $this->amount = $amount;
        $this->balance = $balance;
        $this->time = $time;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Withdrawal request received | Faveremit')
            ->view('emails.withdraw-wallet');
    }
}
