<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCardTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gift_card_country_id',
        'service_id',
        'gift_card_id',
        'card_range_id',
        'gift_card_category_id',
        'rate',
        'card_value',
        'proof',
        'proofs',
        'second_proof',
        'third_proof',
        'rejected_reason',
        'response_image',
        'note',
        'approved_by',
        'rejected_by',
        'status',
        "approved_qty",
        "qty",
        "promo_code",
    ];

    /**
     * Get the user that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(country::class);
    }

    /**
     * Get the gitcard that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gitcard()
    {
        return $this->belongsTo(GiftCard::class);
    }

    public static function getSalesForLast7Days()
    {
        $sevenDaysAgo = \Carbon\Carbon::now()->subDays(7);
        return GiftCardTransaction::where("status", 1)->where('created_at', '>=', $sevenDaysAgo)->selectRaw('DATE(created_at) as day, SUM(rate * card_value) as total')
        ->groupBy('day')
        ->get();
    }

    public static function getSalesForLast4Weeks()
    {
        $fourWeeksAgo = \Carbon\Carbon::now()->subWeeks(4);

        return GiftCardTransaction::where("status", 1)
        ->where('created_at', '>=', $fourWeeksAgo)
            ->selectRaw('YEAR(created_at) as year, WEEK(created_at, 1) as week, SUM(rate * card_value * qty) as total')
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get();
    }

    public static function getSalesByMonth()
    {
        return GiftCardTransaction::where("status", 1)->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(rate * card_value * qty) as total')
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();
    }

    public static function getSalesByYear()
    {
        return GiftCardTransaction::where("status", 1)->selectRaw('YEAR(created_at) as year, SUM(rate * card_value * qty) as total')
        ->groupBy('year')
        ->orderBy('year', 'asc')
        ->get();
    }
}
