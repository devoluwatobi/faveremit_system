<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $device;
    public $os;
    public $ip;
    public $time;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $device, $ip, $os, $time)
    {
        $this->name = $name;
        $this->device = $device;
        $this->ip = $ip;
        $this->os = $os;
        $this->time = $time;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Login Notification | Faveremit')
            ->view('emails.login-notification');
    }
}
