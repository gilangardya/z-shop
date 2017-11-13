<?php
require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// set false for production
$pass_signature = true;

// set LINE channel_access_token and channel_secret
$channel_access_token = "r/r7zHUCvD6o08VoN1QdzzJAK/mgmLyjLbmQCQokYDxaQ3CvKjskxHxEfvcS/f/c5u9lvSkLEF8UlxRHWXy8zu7VH6EXHH2bl7J+DhfWWYWBSta1BR27SWEz12resULqfN9022nngS23pdnLzT/YfgdB04t89/1O/w1cDnyilFU=";
$channel_secret = "217ab5ac4d8d8f042d7607edf59d1433";

// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);

$configs =  [
    'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

// buat route untuk url homepage
$app->get('/', function($req, $res)
{
  echo "Welcome at Slim Framework";
});


function cari_barang($kata) {
    $barang = explode(' ', $kata);
    array_shift($barang);
    $len = count($barang);
    if ($len == 0) {
        return 'Tidak ada barang yang dicari';
    } else {
        $result = "";
        for ($idx = 0; $idx < $len-1; $idx++) {
            $result .= "Hasil pencarian " . $barang[$idx] . "\n";
        }
        $result .= "Hasil pencarian " . $barang[$len-1];
        return $result;
    }
}

// Detail pembayaran
function detail_barang($kata) {
    $kode = explode(' ', $kata);
    array_shift($kode);
    return "Deskripsi pembayaran " . $kode;
}

function deskripsi_barang($kata) {
    $kode = explode(' ', $kode);
    array_shift($kode);
    return "Deskripsi barang " . $kode;
}

function bayar_barang($kata) {
    $kode = explode(' ', $kata);
    array_shift($kode);
    return "Membayar barang " . $kode;
}

function kategori($kata) {
    $kode = explode(' ', $kata);
    array_shift($kode);
    return "Barang-barang dengan kategori " . $kode;
}

function tambah($kata) {
    $kode = explode(' ', $kata);
    array_shift($kode);
    return "Menambahkan " . $kode . " ke keranjang";
}

function hapus($kata) {
    $kode = explode(' ', $kata);
    array_shift($kode);
    return "Menghapus " . $kode . " dari keranjang";
}

function keranjang($kata) {
    return "Isi keranjang\n    1. FD5412";
}

// buat route untuk webhook
$app->post('/webhook', function ($request, $response) use ($bot, $pass_signature)
{
    // get request body and line signature header
    $body        = file_get_contents('php://input');
    $signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';

    // log body and signature
    file_put_contents('php://stderr', 'Body: '.$body);

    if($pass_signature === false)
    {
        // is LINE_SIGNATURE exists in request header?
        if(empty($signature)){
            return $response->withStatus(400, 'Signature not set');
        }

        // is this request comes from LINE?
        if(! SignatureValidator::validateSignature($body, $channel_secret, $signature)){
            return $response->withStatus(400, 'Invalid signature');
        }
    }

    // kode aplikasi nanti disini
    $data = json_decode($body, true);
    if(is_array($data['events'])){
        foreach ($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {
                if($event['message']['type'] == 'text')
                {
                    $query = strtok($event['message']['text'], " ");
                    switch ($query) {
                        case '\cari':
                            $result = $bot->replyText($event['replyToken'], cari_barang($event['message']['text']));
                            break;
                        case '\bayar':
                            $result = $bot->replyText($event['replyToken'], bayar_barang($event['message']['text']));
                            break;
                        case '\detail':
                            $result = $bot->replyText($event['replyToken'], detail_barang($event['message']['text']));
                            break;
                        case '\deskripsi':
                            $result = $bot->replyText($event['replyToken'], deskripsi_barang($event['message']['text']));
                            break;
                        case '\kategori':
                            $result = $bot->replyText($event['replyToken'], kategori($event['message']['text']));
                            break;
                        case '\tambah':
                            $result = $bot->replyText($event['replyToken'], tambah($event['message']['text']));
                            break;
                        case '\hapus':
                            $result = $bot->replyText($event['replyToken'], hapus($event['message']['text']));
                            break;
                        case '\keranjang':
                            $result = $bot->replyText($event['replyToken'], keranjang($event['message']['text']));
                            break;

                        default:
                            $result = $bot->replyText($event['replyToken'], 'Maaf perintah tidak dikenal :))) ' . $userId);
                    }

                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                }
            }
        }
    }
});

$app->get('/profile/{userId}', function($req, $res) use ($bot) {
    // get user profile
    $route  = $req->getAttribute('route');
    $userId = $route->getArgument('userId');
    $result = $bot->getProfile($userId);

    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
});

$app->run();
