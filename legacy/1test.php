<?php
require_once 'config.php';

// Shopier API bilgilerini veritabanından al
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM crypto_settings WHERE setting_key IN ('shopier_username', 'shopier_key')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$api_key = $settings['shopier_username'] ?? ''; // API anahtarınız
$api_secret = $settings['shopier_key'] ?? ''; // Gizli anahtarınız
$siteUrl = 'https://yildizhesap.net/'; // Site URL'niz

if (empty($api_key) || empty($api_secret)) {
    die('Shopier API ayarları eksik!');
}
srand(time()); // Fixed line
$args = array(
'API_key' => $api_key,
'website_index' => 1,
'platform_order_id' => 'guest_order_1754771677_6897b0ddd6d78', // Sipariş numarası
'product_name' => 'Prestashop Mutli Kur Modülü;', // Ürün adı
'product_type' => 1,
'buyer_name' => 'Bera',
'buyer_surname' => 'Ramazan',
'buyer_email' => 'bera_ramazan@gmail.com',
'buyer_account_age' => 578,
'buyer_id_nr' => 3,
'buyer_phone' => '05448412171',
'billing_address' => 'Şabaniye',
'billing_city' => 'Van',
'billing_country' => 'Türkiye',
'billing_postcode' => '65300',
'shipping_address' => 'Şabaniye',
'shipping_city' => 'Van',
'shipping_country' => 'Türkiye',
'shipping_postcode' => '65300',
'total_order_value' => 203326.01,
'currency' => 0,
'platform' => 2,
'is_in_frame' => 0,
'current_language' => 0,
'random_nr' => rand(100000,999999)
);
$data = implode('', $args);
$signature = customHmac($args['random_nr'].$args['platform_order_id'].$args['total_order_value'].$args['currency'], $api_secret);
$signature = base64_encode($signature);
$args['signature'] = $signature;
function customHmac($data, $key)
{
return @hash_hmac('SHA256', $data, $key, true);
}
echo '
<p class="payment_module">
<span class="shopier_payment_title">
Tesekkur
</span>
</p>
<form action="https://www.shopier.com/ShowProduct/api_pay4.php" method="post">';
foreach ($args as $key => $value)
{
echo '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
}
echo '<input type="submit" id="submit_shopier_payment_form" />
</form>
';
?>