<?php
// require 'vendor/autoload.php';
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;



use Mailgun\Mailgun;

class MailgunService
{

    public static function addToMailList($name, $email)
    {
        $mgClient = Mailgun::create('e0a7c06d4a1b3c5b5003a738a5a5f01b-48c092ba-6e1fb798', 'https://api.mailgun.net/v3/');
        $mailing_list = 'hello@msg.faveremit.com';
        $address = $email;
        $name = $name;
        // $vars = array("id" => "123456");

        try {
            $result = $mgClient->mailingList()->member()->create(
                $mailing_list,
                $address,
                $name,
                // $vars
            );
        } catch (Exception $e) {
            Log::error($e);
            return $e;
        }
        return $result;
    }
}
