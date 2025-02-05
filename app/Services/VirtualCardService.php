<?php

namespace App\Services;

use DateTime;
use Carbon\Carbon;
use App\Models\Config;
use App\Models\Verification;
use App\Models\MapleCustomer;
use App\Models\MapleVirtualCard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\MapleVirtualCardReference;


class VirtualCardService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('MAPLERAD_BASE_URL');
        $this->apiKey = env('MAPLERAD_API_KEY');
    }

    /**
     * Make a POST request.
     */
    private function post(string $endpoint, array $data): ?object
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}{$endpoint}", $data)
            ->json();

        return $this->toObject($response);
    }

    private function get(string $endpoint): ?object
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}{$endpoint}")
            ->json();

        return $this->toObject($response);
    }

    /**
     * Make a PATCH request.
     */
    private function patch(string $endpoint, array $data = []): ?object
    {
        $response = Http::withHeaders($this->getHeaders())
            ->patch("{$this->baseUrl}{$endpoint}", $data)
            ->json();

        return $this->toObject($response);
    }

    /**
     * Transform the response to an object.
     */
    private function toObject($response): ?object
    {
        return is_array($response) ? json_decode(json_encode($response)) : null;
    }

    /**
     * Get default headers for HTTP requests.
     */
    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Create a customer.
     */
    public static function createCustomer($user): bool
    {
        $service = new self();
        $data = [
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "email" => $user->email,
            "country" => strtoupper($user->country),
        ];



        $serverOutput = $service->post('/customers', $data);

        if ($serverOutput && $serverOutput->status && isset($serverOutput->data)) {
            MapleCustomer::create([
                "user_id" => $user->id,
                "maple_id" => $serverOutput->data->id,
                "first_name" => $serverOutput->data->first_name,
                "last_name" => $serverOutput->data->last_name,
                "email" => $serverOutput->data->email,
                "country" => $serverOutput->data->country,
                "status" => $serverOutput->data->status,
                "tier" => $serverOutput->data->tier,
                "created_at" => $serverOutput->data->created_at,
                "updated_at" => $serverOutput->data->updated_at,
            ]);
            return true;
        } else if ($serverOutput && $serverOutput->status == false && $serverOutput->message == "customer is already enrolled") {

            $serverOutput = $service->post('/customers', $data);

            MapleCustomer::create([
                "user_id" => $user->id,
                "maple_id" => $serverOutput->data->id,
                "first_name" => $serverOutput->data->first_name,
                "last_name" => $serverOutput->data->last_name,
                "email" => $serverOutput->data->email,
                "country" => $serverOutput->data->country,
                "status" => $serverOutput->data->status,
                "tier" => $serverOutput->data->tier,
                "created_at" => $serverOutput->data->created_at,
                "updated_at" => $serverOutput->data->updated_at,
            ]);
            return true;
        }

        Log::info($data);
        Log::error('Failed to create customer: ', ['response' => $serverOutput]);
        return false;
    }

    /**
     * Upgrade customer tier.
     */
    public static function upgradeCustomer($user, array $payload): bool
    {
        $service = new self();
        $customer = MapleCustomer::where('user_id', $user->id)->firstOrFail();
        $verification = Verification::where('user_id', $user->id)->where('status', 1)->firstOrFail();

        $formats = [
            'd-M-Y',    // 25-May-1923
            'd/M/Y',    // 25/May/1923
            'd/m/Y',    // 25/05/1923
            'd-m-Y',    // 25-05-1923
            'Y-m-d',
            'Y-M-d',
            'Y/M/d',
            'Y/m/d',
        ];

        // $date = $verification->dob;
        // $date = DateTime::createFromFormat('d-m-Y', $date);

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $verification->dob);
            if ($date != null) {
                break;
            }
        }

        // Log::info($date);

        if ($date != null) {
            // Valid date found, format the date or do something with it
            $formattedDate = $date->format('d-m-Y');
            // Continue processing with the valid date
        } else {
            // Handle case where no valid date was found (maybe an error message or other logic)
            Log::error("Invalid date format.");
            return false;
        }

        $data = [
            "customer_id" => $customer->maple_id,
            "dob" => $formattedDate,
            "phone" => [
                "phone_country_code" => $payload['phone_country_code'],
                "phone_number" => $payload['phone_number'],
            ],
            "address" => [
                "street" => $payload['street'],
                "city" => $payload['city'],
                "state" => $payload['state'],
                "country" =>  strtoupper($user->country),
                "postal_code" => $payload['postal_code'],
            ],
            "identification_number" => $verification->value,
        ];

        $serverOutput = $service->patch('/customers/upgrade/tier1', $data);

        if ($serverOutput && $serverOutput->status == false && $serverOutput->message == "customer is already upgraded") {
            $customer->update([
                "dob" => $verification->dob,
                "phone_country_code" => $payload['phone_country_code'],
                "phone_number" => $payload['phone_number'],
                "address_street" => $payload['street'],
                "address_city" => $payload['city'],
                "address_state" => $payload['state'],
                "address_country" =>  strtoupper($user->country),
                "address_postal_code" => $payload['postal_code'],
                "identification_number" => $verification->value,
                "identification_type" => $verification->type,
            ]);
            return true;
        }

        if ($serverOutput && $serverOutput->status) {
            $customer->update([
                "dob" => $verification->dob,
                "phone_country_code" => $payload['phone_country_code'],
                "phone_number" => $payload['phone_number'],
                "address_street" => $payload['street'],
                "address_city" => $payload['city'],
                "address_state" => $payload['state'],
                "address_country" =>  strtoupper($user->country),
                "address_postal_code" => $payload['postal_code'],
                "identification_number" => $verification->value,
                "identification_type" => $verification->type,
            ]);
            return true;
        }

        Log::error('Failed to upgrade customer: ', ['response' => $serverOutput]);
        return false;
    }

    /**
     * Create a virtual card.
     */
    public static function createCard($user, array $payload): bool
    {
        $service = new self();
        $customer = MapleCustomer::where('user_id', $user->id)->firstOrFail();

        $data = [
            "customer_id" => $customer->maple_id,
            "currency" => "USD",
            "type" => "VIRTUAL",
            "auto_approve" => true,
            "brand" => $payload['brand'],
            "amount" => $payload['brand'] === 'VISA' ? 100 : 200,
        ];

        $serverOutput = $service->post('/issuing', $data);

        if ($serverOutput && $serverOutput->status && isset($serverOutput->data)) {
            MapleVirtualCardReference::create([
                "reference" => $serverOutput->data->reference,
                "maple_id" => $customer->maple_id,
                "user_id" => $user->id,
                "data" => json_encode($serverOutput)
            ]);
            return true;
        }

        Log::error('Failed to create card: ', ['response' => $serverOutput]);
        return false;
    }

    /**
     * Fund a card.
     */
    public static function fundCard($cardId, float $amount): mixed
    {
        $service = new self();
        $data = ['amount' => $amount];

        $serverOutput = $service->post("/issuing/{$cardId}/fund", $data);

        if ($serverOutput && $serverOutput->status) {
            return  $serverOutput;
        }

        Log::error('Failed to fund card: ', ['response' => $serverOutput]);
        return null;
    }

    /**
     * Withdraw funds from a card.
     */
    public static function withdrawFunds($cardId, float $amount): bool
    {
        $service = new self();
        $data = ['amount' => $amount];

        $serverOutput = $service->post("/cards/{$cardId}/withdraw", $data);

        if ($serverOutput && $serverOutput->status) {
            return true;
        }

        Log::error('Failed to withdraw funds: ', ['response' => $serverOutput]);
        return false;
    }

    /**
     * Freeze a card.
     */
    public static function freezeCard($cardId): bool
    {
        $service = new self();

        $serverOutput = $service->patch("/cards/{$cardId}/freeze");

        if ($serverOutput && $serverOutput->status) {
            return true;
        }

        Log::error('Failed to freeze card: ', ['response' => $serverOutput]);
        return false;
    }

    /**
     * Unfreeze a card.
     */
    public static function unfreezeCard($cardId): bool
    {
        $service = new self();

        $serverOutput = $service->patch("/cards/{$cardId}/unfreeze");

        if ($serverOutput && $serverOutput->status) {
            return true;
        }

        Log::error('Failed to unfreeze card: ', ['response' => $serverOutput]);
        return false;
    }

    /**
     * Verify BVN.
     */
    public static function verifyBVN($user, string $bvn): bool
    {
        $service = new self();
        $data = ['bvn' => $bvn];

        $serverOutput = $service->post('/identity/bvn', $data);

        if ($serverOutput && $serverOutput->status && isset($serverOutput->data)) {
            Verification::create([
                "user_id" => $user->id,
                "value" => $bvn,
                "dob" => $serverOutput->data->dob,
                "type" => "bvn",
                "status" => 1,
                "name" => "{$serverOutput->data->first_name} {$serverOutput->data->last_name}",
                "data" => json_encode($serverOutput),
                "verification_status" => "Approved",
                "reference" => "maplerad",
            ]);
            return true;
        }

        Log::error('Failed to verify BVN: ', ['response' => $serverOutput]);
        return false;
    }

    public static function updateCards($user): bool
    {
        $customer = MapleCustomer::where("user_id", $user->id)->first();

        if (!$customer || $customer == null) {
            return false;
        }

        $service = new self();

        $serverOutput = $service->get("/issuing?customer_id={$customer->maple_id}");



        if ($serverOutput && $serverOutput->status && isset($serverOutput->data)) {
            foreach ($serverOutput->data as $card) {
                $vcard =   MapleVirtualCard::where("maple_id", $card->id)->first();

                if ($vcard && $vcard != null) {
                    $carbonDate = Carbon::parse($card->balance_updated_at);
                    Log::info("balance => " . $card->balance);
                    $vcard->update([
                        'card_number' => $card->card_number, // Full card number
                        'masked_pan'  => $card->masked_pan, // Masked PAN
                        'expiry'  => $card->expiry, // Expiry date in MM/YY format
                        'cvv'  => $card->cvv, // CVV code
                        'status'  => $card->status,  // ['ACTIVE', 'INACTIVE', 'BLOCKED', 'EXPIRED'], // Card status
                        'balance'  => $card->balance, // lowest denomination; Kobo or Cents", // Balance with precision
                        'balance_updated_at'  => $carbonDate, // Balance updated timestamp
                        'auto_approve'  => $card->auto_approve, // Auto approve flag
                        'street'  => $card->address->street, // Street address
                        'city'  => $card->address->city, // City
                        'state'  => $card->address->state, // State abbreviation
                        'postal_code'  => $card->address->postal_code, // Postal code
                        'country'  => $card->address->country,
                    ]);
                }
            }
            return true;
        }

        Log::error('Failed update Cards: ', ['response' => $serverOutput]);
        return false;
    }

    public static function updateMyCard($user): bool
    {
        $customer = MapleCustomer::where("user_id", $user->id)->first();

        if (!$customer || $customer == null) {
            return false;
        }

        $card = MapleVirtualCard::where("user_id", $user->id)->where("status", "ACTIVE")->first();

        $service = new self();

        $serverOutput = $service->get("/issuing/$card->maple_id");



        if ($serverOutput && $serverOutput->status && isset($serverOutput->data)) {

            $vcard =   MapleVirtualCard::where("maple_id", $card->maple_id)->first();

            if ($vcard && $vcard != null) {
                $carbonDate = Carbon::parse($serverOutput->data->balance_updated_at);
                Log::info("balance => " . $serverOutput->data->balance);
                $vcard->update([
                    'card_number' => $serverOutput->data->card_number, // Full card number
                    'masked_pan'  => $serverOutput->data->masked_pan, // Masked PAN
                    'expiry'  => $serverOutput->data->expiry, // Expiry date in MM/YY format
                    'cvv'  => $serverOutput->data->cvv, // CVV code
                    'status'  => $serverOutput->data->status,  // ['ACTIVE', 'INACTIVE', 'BLOCKED', 'EXPIRED'], // Card status
                    'balance'  => $serverOutput->data->balance, // lowest denomination; Kobo or Cents", // Balance with precision
                    'balance_updated_at'  => $carbonDate, // Balance updated timestamp
                    'auto_approve'  => $serverOutput->data->auto_approve, // Auto approve flag
                    'street'  => $serverOutput->data->address->street, // Street address
                    'city'  => $serverOutput->data->address->city, // City
                    'state'  => $serverOutput->data->address->state, // State abbreviation
                    'postal_code'  => $serverOutput->data->address->postal_code, // Postal code
                    'country'  => $serverOutput->data->address->country,
                ]);
            }

            return true;
        }

        Log::error('Failed to verify BVN: ', ['response' => $serverOutput]);
        return false;
    }

    // Get conversion rate
    public static function getConversionRate(): mixed
    {
        $service = new self();

        $serverOutput = $service->post("/fx/quote", [
            "source_currency" => "NGN",
            "target_currency" => "USD",
            "amount" =>  100000
        ]);

        if ($serverOutput && $serverOutput->status) {
            $ngn_2_usd = $serverOutput->data->rate;
            $ngn_2_usd_charge_num = Config::where('name', 'ngn_2_usd_charge_num')->first();
            $ngn_2_usd_charge_percentage = Config::where('name', 'ngn_2_usd_charge_percentage')->first();

            $serverOutput2 = $service->post("/fx/quote", [
                "source_currency" => "USD",
                "target_currency" => "NGN",
                "amount" =>  100000
            ]);

            if ($serverOutput2 && $serverOutput2->status) {
                $usd_2_ngn = $serverOutput2->data->rate;
                $usd_2_ngn_charge_num = Config::where('name', 'usd_2_ngn_charge_num')->first();
                $usd_2_ngn_charge_percentage = Config::where('name', 'usd_2_ngn_charge_percentage')->first();

                return [
                    "ngn_2_usd" => [
                        "rate" => $ngn_2_usd,
                        "charge_num" => $ngn_2_usd_charge_num->value,
                        "charge_percentage" => $ngn_2_usd_charge_percentage->value,
                    ],
                    "usd_2_ngn" => [
                        "rate" => $usd_2_ngn,
                        "charge_num" => $usd_2_ngn_charge_num->value,
                        "charge_percentage" => $usd_2_ngn_charge_percentage->value,
                    ],
                ];
            }
        }

        return null;
    }
}
