<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class newGiftcardTransaction extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $ngnamount;
    public $receipt_type;
    public $rate;
    public $ref;
    public $status;
    public $data;
    public $time;
    //
    public $giftcard;
    public $country;
    public $card_value;
    public $card_range;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->name = $data['name'];
        $this->ngnamount = $data['ngn_amount'];
        $this->receipt_type = $data['receipt_type'];
        $this->rate = $data['usd_rate'];
        $this->ref = $data['transaction_ref'];
        $this->status = $data['status'];
        $this->time = $data['time'];
        $this->giftcard = $data['giftcard'];
        $this->country = $data['country'];
        $this->card_value = $data['card_value'];
        $this->card_range = $data['card_range'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Giftcard Transaction  | Faveremit')
            ->view('emails.new_giftcard_transaction');
    }
}
