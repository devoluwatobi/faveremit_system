<?php

use Illuminate\Support\Facades\Route;
use Database\Factories\CountryFactory;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\CardRangeController;
use App\Http\Controllers\BankDetailController;
use App\Http\Controllers\BankDetailsController;
use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\BannerPromotionController;
use App\Http\Controllers\BettingTransactionController;
use App\Http\Controllers\BuyGiftCardTransactionController;
use App\Http\Controllers\GiftCardCategoryController;
use App\Http\Controllers\GiftCardsCountryController;
use App\Http\Controllers\WalletTransactionsController;
use App\Http\Controllers\GiftCardTransactionController;
use App\Http\Controllers\RewardWalletTransactionController;
use App\Http\Controllers\WebContact;
use App\Http\Controllers\UserFundBankAccountController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\CustomNotificationController;
use App\Http\Controllers\BuyCryptoTransactionController;
use App\Http\Controllers\FundTransactionController;
use App\Http\Controllers\PromoCodeController;
use App\Http\Controllers\RewardWalletController;
use App\Http\Controllers\MapleVirtualCardController;


//header parameters to allow external server to access

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['cors', 'json.response']], function () {

    Route::post('/redbiller-hook', [FundTransactionController::class, 'webhook']);

    Route::post('/maple-hook', [MapleVirtualCardController::class, 'webhook']);

    Route::get('/test-fcm-2', [HomeController::class, 'testFcm']);

    // ...
    Route::post('/testMailList', [ApiAuthController::class, 'testMailList']);
    Route::get('/getMailList', [ApiAuthController::class, 'getMailList']);
    // testMailList
    // auth routes
    Route::post('/login', [ApiAuthController::class, 'login']);
    Route::post('/register', [ApiAuthController::class, 'register']);
    Route::post('/verify-otp', [ApiAuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [ApiAuthController::class, 'resendOtp']);
    Route::post('/forgot-password', [ApiAuthController::class, 'forgetPass']);
    Route::post('/reset-password', [ApiAuthController::class, 'resetPassword']);
    Route::post('/admin-login', [ApiAuthController::class, 'loginAdmin']);
    Route::post('/check-username', [ApiAuthController::class, 'checkUsername']);
    Route::post('/check-referrer', [ApiAuthController::class, 'checkReferrer']);
    Route::get('/show-giftcards/{id}', [GiftCardController::class, 'cardData']);
    Route::get('/show-giftcards', [GiftCardController::class, 'index']);

    // debug routes
    Route::get('/get-bill-cats', [UtilityController::class, 'index']);
    Route::get('/get-banks', [BankDetailsController::class, 'getBanks']);

    // Website Contacts
    Route::post('/contact-us', [WebContact::class, 'webContact']);

    // new email routes
    Route::post('/login-email', [ApiAuthController::class, 'loginEmail']);
    Route::post(
        '/verify-email-otp',
        [ApiAuthController::class, 'verifyEmailOtp']
    );
    Route::post('/forgot-email-password', [ApiAuthController::class, 'forgetEmailPass']);
    Route::post(
        '/resend-email-otp',
        [ApiAuthController::class, 'resendEmailOtp']
    );



    // MIDDLEWARE FOR AUTH APIS
    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout.api');

        Route::post('/delete-account', [ApiAuthController::class, 'deleteAccountRequest']);

        Route::post('/update-account', [ApiAuthController::class, 'update']);
        Route::post('/update-password', [ApiAuthController::class, 'updatePassword']);
        Route::post('/notify-login', [ApiAuthController::class, 'notifyLogin']);

        Route::post('/update-fcm', [ApiAuthController::class, 'updateFcm']);

        // get requests
        Route::get('/home', [HomeController::class, 'index']);
        Route::get('/transactions', [HomeController::class, 'allTrans']);
        Route::get('/transactions/{id}', [HomeController::class, 'singleTrans']);
        Route::get('/profile', [HomeController::class, 'profile']);
        Route::get('/bank-account', [BankDetailsController::class, 'index']);
        Route::post('/verify-bank-account', [BankDetailsController::class, 'verify']);


        Route::get('/get-profile', [ApiAuthController::class, 'getProfile']);

        Route::post('/add-service', [ServiceController::class, 'store']);
        Route::post('/update-service', [ServiceController::class, 'update']);
        Route::post('/add-config', [ConfigController::class, 'store']);

        //   bank and withdraws
        Route::post('/add-bank-acount', [BankDetailsController::class, 'store']);
        Route::post('/update-bank-acount', [BankDetailsController::class, 'updateBank']);
        Route::post('/withdraw-wallet', [WalletTransactionsController::class, 'withdraw']);
        Route::get('/get-withdrawal-history', [WalletTransactionsController::class, 'index']);
        Route::get('/get-all-withdrawal-history', [WalletTransactionsController::class, 'showAll']);
        Route::get('/get-single-withdrawal/{id}', [WalletTransactionsController::class, 'getTransaction']);
        Route::post('/cancel-withdrawal-transaction', [WalletTransactionsController::class, 'cancel']);
        Route::get('/get-redbiller-banks', [BankDetailsController::class, 'getRedBillerBanks']);
        Route::post('/update-red-bank-account', [BankDetailsController::class, 'verifyUpdateRedBillerAccount']);



        // giftcards
        Route::get('/get-giftcard-transactions', [GiftCardTransactionController::class, 'index']);
        Route::get('/get-giftcards', [GiftCardController::class, 'index']);
        Route::get('/get-giftcards/{id}', [GiftCardController::class, 'cardData']);
        Route::post('/make-giftcard-transaction', [GiftCardTransactionController::class, 'makeTransaction']);
        Route::post('/cancel-giftcard-transaction', [GiftCardTransactionController::class, 'cancel']);
        Route::get('/get-giftcard-transaction/{id}', [GiftCardTransactionController::class, 'getTransaction']);

        Route::get('/get-giftcards-2', [GiftCardController::class, 'AdminIndex2']);

        // utilities
        Route::get('/get-utility-list', [UtilityController::class, 'utilityList']);
        Route::get('/get-network-list', [UtilityController::class, 'getNetworks']);
        Route::get('/get-data-list', [UtilityController::class, 'getDataList']);
        Route::get('/get-tv-list', [UtilityController::class, 'getTvList']);
        Route::get('/get-tv-packages', [UtilityController::class, 'getTvPackages']);
        Route::get('/get-bill-status/{id}', [UtilityController::class, 'getBillStatus']);
        Route::get('/get-electric-list', [UtilityController::class, 'getElectricList']);
        Route::get('/get-electric-types', [UtilityController::class, 'getElectricTypes']);
        Route::get('/get-utility-transaction/{id}', [UtilityController::class, 'getTransaction']);


        //post requests
        Route::post('/buy-airtime', [UtilityController::class, 'buyAirtime']);
        Route::post('/buy-data', [UtilityController::class, 'buyData']);
        Route::post('/verify-meter', [UtilityController::class, 'verifyMeter']);
        Route::post('/buy-electricity', [UtilityController::class, 'buyElectricity']);
        Route::post('/buy-cable', [UtilityController::class, 'buyCable']);
        Route::post('/withdraw-reward', [RewardWalletTransactionController::class, 'withdraw']);

        // Buy Giftcard
        Route::get('/get-buy-countries', [BuyGiftCardTransactionController::class, 'countries']);
        Route::get('/get-country-cards/{iso}', [BuyGiftCardTransactionController::class, 'cardsByCountry']);
        Route::post('/complete-giftcard-buy', [BuyGiftCardTransactionController::class, 'makeOrder']);
        Route::get('/buy-charge', [BuyGiftCardTransactionController::class, 'kdcChargeAmount']);
        Route::get('/buy-giftcard-transaction/{id}', [BuyGiftCardTransactionController::class, 'getTransaction']);


        // Betting
        Route::get('/get-bet-platforms', [BettingTransactionController::class, 'getBettingPlatforms']);
        Route::post('/verify-bet-account', [BettingTransactionController::class, 'verifyBettingAccount']);
        Route::post('/fund-bet-account', [BettingTransactionController::class, 'fundBettingAccount']);

        // Fund
        Route::post('/create-fund-account', [UserFundBankAccountController::class, 'create']);
        Route::get('/fetch-fund-account', [UserFundBankAccountController::class, 'index']);
        Route::get('/fetch-fund-trx/{id}', [FundTransactionController::class, 'getTransaction']);

        // verification
        Route::get('/get-verifications', [VerificationController::class, 'index']);
        Route::post('/verify-bvn', [VerificationController::class, 'verifyBVN']);
        Route::post('/verify-nin', [VerificationController::class, 'verifyNIN']);
        Route::post('/verify-phone-number-otp', [VerificationController::class, 'verifyNumber']);
        Route::post('/send-phone-verification-otp', [VerificationController::class, 'sendOtp']);

        // ssend money
        Route::post('/get-receiver', [WalletTransactionsController::class, 'getReceiver']);
        Route::post('/send-to-receiver', [WalletTransactionsController::class, 'sendToUsername']);

        Route::post('/verify-send-bank', [WalletTransactionsController::class, 'verifyBank']);
        Route::post('/send-to-bank-request', [WalletTransactionsController::class, 'sendToBankRequest']);


        Route::post('/buy-crypto-request', [BuyCryptoTransactionController::class, 'store']);
        Route::get('/get-buy-crypto-transaction/{id}', [BuyCryptoTransactionController::class, 'getTransaction']);

        // promo
        Route::get('/fetch-promo-code/{code}', [PromoCodeController::class, 'fetch']);

        // Red
        Route::post('/buy-red-airtime', [UtilityController::class, 'buyRedAirtime']);
        Route::get('/get-red-data-list/{product}', [UtilityController::class, 'getRedDataList']);
        Route::post('/buy-red-data', [UtilityController::class, 'buyRedData']);
        Route::get('/get-red-tv-list/{product}', [UtilityController::class, 'getRedTvPackages']);
        Route::post('/buy-red-cable', [UtilityController::class, 'buyRedTVSub']);
        Route::post('/verify-red-meter', [UtilityController::class, 'verifyRedMeter']);
        Route::post('/verify-red-decoder', [UtilityController::class, 'verifyRedDecoder']);
        Route::post('/buy-red-electricity', [UtilityController::class, 'buyRedElectricity']);

        Route::get('/utility/services/internet', [UtilityController::class, 'getInternetServices']);
        Route::get('/utility/services/internet/{product}', [UtilityController::class, 'getRedInternetList']);
        Route::post('/utility/services/verify-internet', [UtilityController::class, 'verifyRedInternetDevice']);
        Route::post('/utility/services/purchase-internet', [UtilityController::class, 'buyRedInternet']);

        // reward
        Route::post('/claim-reward', [RewardWalletController::class, 'claim']);
        Route::get('/rewards', [RewardWalletController::class, 'index']);


        // VirtualCard
        Route::post('/virtual-cards/create', [MapleVirtualCardController::class, 'createCard']);
        Route::get('/virtual-cards', [MapleVirtualCardController::class, 'getCards']);
        Route::get('/virtual-card', [MapleVirtualCardController::class, 'getCards']);
        Route::get('/virtual-card/data', [MapleVirtualCardController::class, 'getData']);
        Route::post('/virtual-card/fund', [MapleVirtualCardController::class, 'fundMyCard']);
        Route::get('/fx/rates', [MapleVirtualCardController::class, 'getRate']);

        // notifications
        Route::get('/get-notifications', [CustomNotificationController::class, 'index']);

        // leaderboard
        Route::get('/leaderboard/giftcard', [HomeController::class, 'showGiftLeaders']);
    });


    // admin groups

    Route::name('admin.')->prefix('admin')->group(function () {
        Route::post('/login', [ApiAuthController::class, 'loginAdmin']);
        Route::post('/login-email', [ApiAuthController::class, 'loginEmailAdmin']);
        // Route::post('/update-old-users', [ApiAuthController::class, 'addOldAccounts']);

        Route::group(['middleware' => ['auth:api']], function () {
            Route::get('/home', [HomeController::class, 'adminIndex']);
            Route::get('/transactions', [HomeController::class, 'adminTrans']);
            Route::get('/user-transactions/{id}', [HomeController::class, 'allUserTrans']);
            Route::get('/prev-transactions', [HomeController::class, 'adminPrevTrans']);
            Route::get('/transactions/{id}', [HomeController::class, 'singleAdminTrans']);
            Route::post('/update-fcm', [ApiAuthController::class, 'updateFcm']);

            /* gitfcard */
            Route::post('/add-giftcard', [GiftCardController::class, 'store']);
            Route::post('/update-giftcard', [GiftCardController::class, 'update']);
            Route::post('/add-giftcard-country', [GiftCardsCountryController::class, 'store']);
            Route::delete('/remove-giftcard-country/{id}', [GiftCardsCountryController::class, 'destroy']);
            Route::post('/add-country-range', [CardRangeController::class, 'store']);
            Route::post('/update-country-range', [CardRangeController::class, 'updateRange']);
            Route::post('/remove-country-range', [GiftCardsCountryController::class, 'store']);
            Route::get('/get-giftcards', [GiftCardController::class, 'AdminIndex']);
            Route::post('/activate-giftcard', [GiftCardController::class, 'activate']);
            Route::post('/deactivate-giftcard', [GiftCardController::class, 'deactivate']);
            Route::post('/activate-country', [GiftCardsCountryController::class, 'activate']);
            Route::post('/deactivate-country', [GiftCardsCountryController::class, 'deactivate']);
            Route::post('/activate-range', [CardRangeController::class, 'activate']);
            Route::post('/deactivate-range', [CardRangeController::class, 'deactivate']);
            Route::post('/add-giftcard-category', [CardRangeController::class, 'createRangeCategory']);
            Route::post('/update-giftcard-category', [CardRangeController::class, 'updateRangeCategory']);


            Route::get('/get-giftcards/{id}', [GiftCardController::class, 'AdminSingleCard']);
            Route::get('/get-giftcards/country/{id}', [GiftCardController::class, 'AdminSingleCountry']);
            Route::get('/get-countries', [CountryController::class, 'index']);
            Route::get('/get-giftcard-transactions', [GiftCardTransactionController::class, 'AdminIndex']);

            Route::get('/get-giftcard-transaction/{id}', [GiftCardTransactionController::class, 'adminGetTransaction']);
            Route::post('/approve-giftcard-transaction', [GiftCardTransactionController::class, 'approveTransaction']);
            Route::post('/reject-giftcard-transaction', [GiftCardTransactionController::class, 'rejectTransaction']);




            Route::post('/update-btc-rate', [ConfigController::class, 'updateBTCRate']);

            // utility
            Route::post('/add-utility-servie', [UtilityController::class, 'mianStore']);

            // withdrawals
            Route::get('/get-single-withdrawal/{id}', [WalletTransactionsController::class, 'getTransaction']);
            Route::post('/approve-withdrawal', [WalletTransactionsController::class, 'accept']);
            Route::post('/reject-withdrawal', [WalletTransactionsController::class, 'reject']);

            Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout.api');

            // Admin Parols
            Route::get('/all-users', [HomeController::class, 'allUsers']);
            Route::post('/change-user-role', [ApiAuthController::class, 'changeRole']);
            Route::post('/deactivate-account', [ApiAuthController::class, 'deactivateAccount']);
            Route::post('/activate-account', [ApiAuthController::class, 'activateAccount']);

            // banner
            Route::post('/upload-banner', [BannerPromotionController::class, 'addNew']);
            Route::post('/update-banner', [BannerPromotionController::class, 'update']);
            Route::post('/remove-banner', [BannerPromotionController::class, 'remove']);

            // delete requests
            Route::post('/delete-card-range', [CardRangeController::class, 'delete']);
            Route::post('/delete-giftcard-category', [GiftCardCategoryController::class, 'delete']);
            Route::post('/delete-giftcard-country', [GiftCardsCountryController::class, 'delete']);

            Route::get('/get-giftcards-2', [GiftCardController::class, 'AdminIndex2']);
            Route::post('/update-giftcard-rates', [GiftCardController::class, 'updateRates']);

            // push Notification
            Route::post('/send-user-push', [CustomNotificationController::class, 'sendPushToUsers']);

            // promo
            Route::get('/all-promo-codes', [PromoCodeController::class, 'index']);
            Route::post('/create-promo-code', [PromoCodeController::class, 'create']);
        });
    });
});
