<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class newCryptoTransaction extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $ngnamount;
    public $usdamount;
    public $rate;
    public $ref;
    public $status;
    public $data;
    public $time;
    //
    public $crypto;
    public $wallet_type;
    public $crypto_amount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->name = $data['name'];
        $this->ngnamount = $data['ngn_amount'];
        $this->usdamount = $data['usd_amount'];
        $this->rate = $data['usd_rate'];
        $this->ref = $data['transaction_ref'];
        $this->status = $data['status'];
        $this->time = $data['time'];
        $this->crypto = $data['crypto'];
        $this->wallet_type = $data['wallet_type'];
        $this->crypto_amount = $data['crypto_amount'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Crypto Transaction  | Faveremit')
            ->view('emails.new_crypto_transaction');
    }
}
