<?php

use Illuminate\Support\Facades\Route;

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

    Route::post('/redbiller-hook', [App\Http\Controllers\FundTransactionController::class, 'webhook']);

    Route::post('/maple-hook', [App\Http\Controllers\MapleVirtualCardController::class, 'webhook']);

    Route::get('/test-fcm-2', [App\Http\Controllers\HomeController::class, 'testFcm']);

    // ...
    Route::post('/testMailList', [App\Http\Controllers\Auth\ApiAuthController::class, 'testMailList']);
    Route::get('/getMailList', [App\Http\Controllers\Auth\ApiAuthController::class, 'getMailList']);
    // testMailList
    // auth routes
    Route::post('/login', [App\Http\Controllers\Auth\ApiAuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Auth\ApiAuthController::class, 'register']);
    Route::post('/verify-otp', [App\Http\Controllers\Auth\ApiAuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [App\Http\Controllers\Auth\ApiAuthController::class, 'resendOtp']);
    Route::post('/forgot-password', [App\Http\Controllers\Auth\ApiAuthController::class, 'forgetPass']);
    Route::post('/reset-password', [App\Http\Controllers\Auth\ApiAuthController::class, 'resetPassword']);
    Route::post('/admin-login', [App\Http\Controllers\Auth\ApiAuthController::class, 'loginAdmin']);
    Route::post('/check-username', [App\Http\Controllers\Auth\ApiAuthController::class, 'checkUsername']);
    Route::post('/check-referrer', [App\Http\Controllers\Auth\ApiAuthController::class, 'checkReferrer']);
    Route::get('/show-giftcards/{id}', [App\Http\Controllers\GiftCardController::class, 'cardData']);
    Route::get('/show-giftcards', [App\Http\Controllers\GiftCardController::class, 'index']);

    // debug routes
    Route::get('/get-bill-cats', [App\Http\Controllers\UtilityController::class, 'index']);
    Route::get('/get-banks', [App\Http\Controllers\BankDetailsController::class, 'getBanks']);

    // Website Contacts
    Route::post('/contact-us', [App\Http\Controllers\WebContact::class, 'webContact']);

    // new email routes
    Route::post('/login-email', [App\Http\Controllers\Auth\ApiAuthController::class, 'loginEmail']);
    Route::post(
        '/verify-email-otp',
        [App\Http\Controllers\Auth\ApiAuthController::class, 'verifyEmailOtp']
    );
    Route::post('/forgot-email-password', [App\Http\Controllers\Auth\ApiAuthController::class, 'forgetEmailPass']);
    Route::post(
        '/resend-email-otp',
        [App\Http\Controllers\Auth\ApiAuthController::class, 'resendEmailOtp']
    );



    // MIDDLEWARE FOR AUTH APIS
    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('/logout', [App\Http\Controllers\Auth\ApiAuthController::class, 'logout'])->name('logout.api');

        Route::post('/delete-account', [App\Http\Controllers\Auth\ApiAuthController::class, 'deleteAccountRequest']);

        Route::post('/update-account', [App\Http\Controllers\Auth\ApiAuthController::class, 'update']);
        Route::post('/update-password', [App\Http\Controllers\Auth\ApiAuthController::class, 'updatePassword']);
        Route::post('/notify-login', [App\Http\Controllers\Auth\ApiAuthController::class, 'notifyLogin']);

        Route::post('/update-fcm', [App\Http\Controllers\Auth\ApiAuthController::class, 'updateFcm']);

        // get requests
        Route::get('/home', [App\Http\Controllers\HomeController::class, 'index']);
        Route::get('/transactions', [App\Http\Controllers\HomeController::class, 'allTrans']);
        Route::get('/transactions/{id}', [App\Http\Controllers\HomeController::class, 'singleTrans']);
        Route::get('/profile', [App\Http\Controllers\HomeController::class, 'profile']);
        Route::get('/bank-account', [App\Http\Controllers\BankDetailsController::class, 'index']);
        Route::post('/verify-bank-account', [App\Http\Controllers\BankDetailsController::class, 'verify']);


        Route::get('/get-profile', [App\Http\Controllers\Auth\ApiAuthController::class, 'getProfile']);

        Route::post('/add-service', [App\Http\Controllers\ServiceController::class, 'store']);
        Route::post('/update-service', [App\Http\Controllers\ServiceController::class, 'update']);
        Route::post('/add-config', [App\Http\Controllers\ConfigController::class, 'store']);

        //   bank and withdraws
        Route::post('/add-bank-acount', [App\Http\Controllers\BankDetailsController::class, 'store']);
        Route::post('/update-bank-acount', [App\Http\Controllers\BankDetailsController::class, 'updateBank']);
        Route::post('/withdraw-wallet', [App\Http\Controllers\WalletTransactionsController::class, 'withdraw']);
        Route::get('/get-withdrawal-history', [App\Http\Controllers\WalletTransactionsController::class, 'index']);
        Route::get('/get-all-withdrawal-history', [App\Http\Controllers\WalletTransactionsController::class, 'showAll']);
        Route::get('/get-single-withdrawal/{id}', [App\Http\Controllers\WalletTransactionsController::class, 'getTransaction']);
        Route::post('/cancel-withdrawal-transaction', [App\Http\Controllers\WalletTransactionsController::class, 'cancel']);
        Route::get('/get-redbiller-banks', [App\Http\Controllers\BankDetailsController::class, 'getRedBillerBanks']);
        Route::post('/update-red-bank-account', [App\Http\Controllers\BankDetailsController::class, 'verifyUpdateRedBillerAccount']);

        // giftcards
        Route::get('/get-giftcard-transactions', [App\Http\Controllers\GiftCardTransactionController::class, 'index']);
        Route::get('/get-giftcards', [App\Http\Controllers\GiftCardController::class, 'index']);
        Route::get('/get-giftcards/{id}', [App\Http\Controllers\GiftCardController::class, 'cardData']);
        Route::post('/make-giftcard-transaction', [App\Http\Controllers\GiftCardTransactionController::class, 'makeTransaction']);
        Route::post('/cancel-giftcard-transaction', [App\Http\Controllers\GiftCardTransactionController::class, 'cancel']);
        Route::get('/get-giftcard-transaction/{id}', [App\Http\Controllers\GiftCardTransactionController::class, 'getTransaction']);

        Route::get('/get-giftcards-2', [App\Http\Controllers\GiftCardController::class, 'AdminIndex2']);

        // utilities
        Route::get('/get-utility-list', [App\Http\Controllers\UtilityController::class, 'utilityList']);
        Route::get('/get-network-list', [App\Http\Controllers\UtilityController::class, 'getNetworks']);
        Route::get('/get-data-list', [App\Http\Controllers\UtilityController::class, 'getDataList']);
        Route::get('/get-tv-list', [App\Http\Controllers\UtilityController::class, 'getTvList']);
        Route::get('/get-tv-packages', [App\Http\Controllers\UtilityController::class, 'getTvPackages']);
        Route::get('/get-bill-status/{id}', [App\Http\Controllers\UtilityController::class, 'getBillStatus']);
        Route::get('/get-electric-list', [App\Http\Controllers\UtilityController::class, 'getElectricList']);
        Route::get('/get-electric-types', [App\Http\Controllers\UtilityController::class, 'getElectricTypes']);
        Route::get('/get-utility-transaction/{id}', [App\Http\Controllers\UtilityController::class, 'getTransaction']);

        //post requests
        Route::post('/buy-airtime', [App\Http\Controllers\UtilityController::class, 'buyAirtime']);
        Route::post('/buy-data', [App\Http\Controllers\UtilityController::class, 'buyData']);
        Route::post('/verify-meter', [App\Http\Controllers\UtilityController::class, 'verifyMeter']);
        Route::post('/buy-electricity', [App\Http\Controllers\UtilityController::class, 'buyElectricity']);
        Route::post('/buy-cable', [App\Http\Controllers\UtilityController::class, 'buyCable']);
        Route::post('/withdraw-reward', [App\Http\Controllers\RewardWalletTransactionController::class, 'withdraw']);

        // Buy Giftcard
        Route::get('/get-buy-countries', [App\Http\Controllers\BuyGiftCardTransactionController::class, 'countries']);
        Route::get('/get-country-cards/{iso}', [App\Http\Controllers\BuyGiftCardTransactionController::class, 'cardsByCountry']);
        Route::post('/complete-giftcard-buy', [App\Http\Controllers\BuyGiftCardTransactionController::class, 'makeOrder']);
        Route::get('/buy-charge', [App\Http\Controllers\BuyGiftCardTransactionController::class, 'kdcChargeAmount']);
        Route::get('/buy-giftcard-transaction/{id}', [App\Http\Controllers\BuyGiftCardTransactionController::class, 'getTransaction']);

        // Betting
        Route::get('/get-bet-platforms', [App\Http\Controllers\BettingTransactionController::class, 'getBettingPlatforms']);
        Route::post('/verify-bet-account', [App\Http\Controllers\BettingTransactionController::class, 'verifyBettingAccount']);
        Route::post('/fund-bet-account', [App\Http\Controllers\BettingTransactionController::class, 'fundBettingAccount']);

        // Fund
        Route::post('/create-fund-account', [App\Http\Controllers\UserFundBankAccountController::class, 'create']);
        Route::get('/fetch-fund-account', [App\Http\Controllers\UserFundBankAccountController::class, 'index']);
        Route::get('/fetch-fund-trx/{id}', [App\Http\Controllers\FundTransactionController::class, 'getTransaction']);

        // verification
        Route::get('/get-verifications', [App\Http\Controllers\VerificationController::class, 'index']);
        Route::post('/verify-bvn', [App\Http\Controllers\VerificationController::class, 'verifyBVN']);
        Route::post('/verify-nin', [App\Http\Controllers\VerificationController::class, 'verifyNIN']);
        Route::post('/verify-phone-number-otp', [App\Http\Controllers\VerificationController::class, 'verifyNumber']);
        Route::post('/send-phone-verification-otp', [App\Http\Controllers\VerificationController::class, 'sendOtp']);

        // ssend money
        Route::post('/get-receiver', [App\Http\Controllers\WalletTransactionsController::class, 'getReceiver']);
        Route::post('/send-to-receiver', [App\Http\Controllers\WalletTransactionsController::class, 'sendToUsername']);

        Route::post('/verify-send-bank', [App\Http\Controllers\WalletTransactionsController::class, 'verifyBank']);
        Route::post('/send-to-bank-request', [App\Http\Controllers\WalletTransactionsController::class, 'sendToBankRequest']);


        Route::post('/buy-crypto-request', [App\Http\Controllers\BuyCryptoTransactionController::class, 'store']);
        Route::get('/get-buy-crypto-transaction/{id}', [App\Http\Controllers\BuyCryptoTransactionController::class, 'getTransaction']);

        // promo
        Route::get('/fetch-promo-code/{code}', [App\Http\Controllers\PromoCodeController::class, 'fetch']);

        // Red
        Route::post('/buy-red-airtime', [App\Http\Controllers\UtilityController::class, 'buyRedAirtime']);
        Route::get('/get-red-data-list/{product}', [App\Http\Controllers\UtilityController::class, 'getRedDataList']);
        Route::post('/buy-red-data', [App\Http\Controllers\UtilityController::class, 'buyRedData']);
        Route::get('/get-red-tv-list/{product}', [App\Http\Controllers\UtilityController::class, 'getRedTvPackages']);
        Route::post('/buy-red-cable', [App\Http\Controllers\UtilityController::class, 'buyRedTVSub']);
        Route::post('/verify-red-meter', [App\Http\Controllers\UtilityController::class, 'verifyRedMeter']);
        Route::post('/verify-red-decoder', [App\Http\Controllers\UtilityController::class, 'verifyRedDecoder']);
        Route::post('/buy-red-electricity', [App\Http\Controllers\UtilityController::class, 'buyRedElectricity']);

        Route::get('/utility/services/internet', [App\Http\Controllers\UtilityController::class, 'getInternetServices']);
        Route::get('/utility/services/internet/{product}', [App\Http\Controllers\UtilityController::class, 'getRedInternetList']);
        Route::post('/utility/services/verify-internet', [App\Http\Controllers\UtilityController::class, 'verifyRedInternetDevice']);
        Route::post('/utility/services/purchase-internet', [App\Http\Controllers\UtilityController::class, 'buyRedInternet']);

        // reward
        Route::post('/claim-reward', [App\Http\Controllers\RewardWalletController::class, 'claim']);
        Route::get('/rewards', [App\Http\Controllers\RewardWalletController::class, 'index']);


        // VirtualCard
        Route::post('/virtual-cards/create', [App\Http\Controllers\MapleVirtualCardController::class, 'createCard']);
        Route::get('/virtual-cards', [App\Http\Controllers\MapleVirtualCardController::class, 'getCards']);
        Route::get('/virtual-card', [App\Http\Controllers\MapleVirtualCardController::class, 'getCards']);
        Route::get('/virtual-card/data', [App\Http\Controllers\MapleVirtualCardController::class, 'getData']);
        Route::post('/virtual-card/fund', [App\Http\Controllers\MapleVirtualCardController::class, 'fundMyCard']);
        Route::get('/fx/rates', [App\Http\Controllers\MapleVirtualCardController::class, 'getRate']);

        // notifications
        Route::get('/get-notifications', [App\Http\Controllers\CustomNotificationController::class, 'index']);

        // leaderboard
        Route::get('/leaderboard/giftcard', [App\Http\Controllers\HomeController::class, 'showGiftLeaders']);
    });

    // admin groups

    Route::name('admin.')->prefix('admin')->group(function () {
        Route::post('/login', [App\Http\Controllers\Auth\ApiAuthController::class, 'loginAdmin']);
        Route::post('/login-email', [App\Http\Controllers\Auth\ApiAuthController::class, 'loginEmailAdmin']);


        Route::group(['middleware' => ['auth:api']], function () {
            Route::get('/home', [App\Http\Controllers\HomeController::class, 'adminIndex']);
            Route::get('/transactions', [App\Http\Controllers\HomeController::class, 'adminTrans']);
            Route::get('/user-transactions/{id}', [App\Http\Controllers\HomeController::class, 'allUserTrans']);
            Route::post('/update-fcm', [App\Http\Controllers\Auth\ApiAuthController::class, 'updateFcm']);

            /* gitfcard */
            Route::post('/add-giftcard', [App\Http\Controllers\GiftCardController::class, 'store']);
            Route::post('/update-giftcard', [App\Http\Controllers\GiftCardController::class, 'update']);
            Route::post('/add-giftcard-country', [App\Http\Controllers\GiftCardsCountryController::class, 'store']);
            Route::delete('/remove-giftcard-country/{id}', [App\Http\Controllers\GiftCardsCountryController::class, 'destroy']);
            Route::post('/add-country-range', [App\Http\Controllers\CardRangeController::class, 'store']);
            Route::post('/update-country-range', [App\Http\Controllers\CardRangeController::class, 'updateRange']);
            Route::post('/remove-country-range', [App\Http\Controllers\GiftCardsCountryController::class, 'store']);
            Route::get('/get-giftcards', [App\Http\Controllers\GiftCardController::class, 'AdminIndex']);
            Route::post('/activate-giftcard', [App\Http\Controllers\GiftCardController::class, 'activate']);
            Route::post('/deactivate-giftcard', [App\Http\Controllers\GiftCardController::class, 'deactivate']);
            Route::post('/activate-country', [App\Http\Controllers\GiftCardsCountryController::class, 'activate']);
            Route::post('/deactivate-country', [App\Http\Controllers\GiftCardsCountryController::class, 'deactivate']);
            Route::post('/activate-range', [App\Http\Controllers\CardRangeController::class, 'activate']);
            Route::post('/deactivate-range', [App\Http\Controllers\CardRangeController::class, 'deactivate']);
            Route::post('/add-giftcard-category', [App\Http\Controllers\CardRangeController::class, 'createRangeCategory']);
            Route::post('/update-giftcard-category', [App\Http\Controllers\CardRangeController::class, 'updateRangeCategory']);

            Route::get('/get-giftcards/{id}', [App\Http\Controllers\GiftCardController::class, 'AdminSingleCard']);
            Route::get('/get-giftcards/country/{id}', [App\Http\Controllers\GiftCardController::class, 'AdminSingleCountry']);
            Route::get('/get-countries', [App\Http\Controllers\CountryController::class, 'index']);
            Route::get('/get-giftcard-transactions', [App\Http\Controllers\GiftCardTransactionController::class, 'AdminIndex']);

            Route::get('/get-giftcard-transaction/{id}', [App\Http\Controllers\GiftCardTransactionController::class, 'adminGetTransaction']);
            Route::post('/approve-giftcard-transaction', [App\Http\Controllers\GiftCardTransactionController::class, 'approveTransaction']);
            Route::post('/reject-giftcard-transaction', [App\Http\Controllers\GiftCardTransactionController::class, 'rejectTransaction']);

            Route::post('/update-btc-rate', [App\Http\Controllers\ConfigController::class, 'updateBTCRate']);

            // utility
            Route::post('/add-utility-servie', [App\Http\Controllers\UtilityController::class, 'mianStore']);

            // withdrawals
            Route::get('/get-single-withdrawal/{id}', [App\Http\Controllers\WalletTransactionsController::class, 'getTransaction']);
            Route::post('/approve-withdrawal', [App\Http\Controllers\WalletTransactionsController::class, 'accept']);
            Route::post('/reject-withdrawal', [App\Http\Controllers\WalletTransactionsController::class, 'reject']);

            Route::post('/logout', [App\Http\Controllers\Auth\ApiAuthController::class, 'logout'])->name('logout.api');

            // Admin Parols
            Route::get('/all-users', [App\Http\Controllers\HomeController::class, 'allUsers']);
            Route::post('/change-user-role', [App\Http\Controllers\Auth\ApiAuthController::class, 'changeRole']);
            Route::post('/deactivate-account', [App\Http\Controllers\Auth\ApiAuthController::class, 'deactivateAccount']);
            Route::post('/activate-account', [App\Http\Controllers\Auth\ApiAuthController::class, 'activateAccount']);

            // banner
            Route::post('/upload-banner', [App\Http\Controllers\BannerPromotionController::class, 'addNew']);
            Route::post('/update-banner', [App\Http\Controllers\BannerPromotionController::class, 'update']);
            Route::post('/remove-banner', [App\Http\Controllers\BannerPromotionController::class, 'remove']);

            // delete requests
            Route::post('/delete-card-range', [App\Http\Controllers\CardRangeController::class, 'delete']);
            Route::post('/delete-giftcard-category', [App\Http\Controllers\GiftCardCategoryController::class, 'delete']);
            Route::post('/delete-giftcard-country', [App\Http\Controllers\GiftCardsCountryController::class, 'delete']);

            Route::get('/get-giftcards-2', [App\Http\Controllers\GiftCardController::class, 'AdminIndex2']);
            Route::post('/update-giftcard-rates', [App\Http\Controllers\GiftCardController::class, 'updateRates']);

            // push Notification
            Route::post('/send-user-push', [App\Http\Controllers\CustomNotificationController::class, 'sendPushToUsers']);

            // promo
            Route::get('/all-promo-codes', [App\Http\Controllers\PromoCodeController::class, 'index']);
            Route::post('/create-promo-code', [App\Http\Controllers\PromoCodeController::class, 'create']);

            Route::prefix('dashboard')->group(function () {  // notifications routes
                Route::controller(App\Http\Controllers\DashboardController::class)->group(function () {
                    Route::get('index', 'index');
                    Route::get('users/index', 'userStatIndex');
                    Route::get('users/all', 'indexMethod');
                    Route::post('users/graph', 'getUsersGraphMethod');
                    Route::get('users-count', 'usersCountMethod');
                });
            }); //end of notifications routes

        });
    });
});
