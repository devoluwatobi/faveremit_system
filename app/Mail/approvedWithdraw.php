<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class approvedWithdraw extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;
    public $status;
    public $bank;
    public $time;
    public $account_number;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->name = $data['name'];
        $this->amount = $data['amount'];
        $this->bank = $data['bank'];
        $this->account_number = $data['account_number'];
        $this->status = $data['status'];
        $this->time = $data['time'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your withdrawal has been processed | Faveremit')
            ->view('emails.approvedWithdraw');
    }
}
