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
                        
                        default:
                            $result = $bot->replyText($event['replyToken'], 'Maaf perintah tidak dikenal :))');
                    }

                    // if ($event['message']['text'] == '\cari') {
                    //     $result = $bot->replyText($event['replyToken'], 'Mau cari apa?');
                    // } else {
                    //     $result = $bot->replyText($event['replyToken'], 'Ini pesan balasan lewat GitHub yang otomatis');
                    // }
                    // send same message as reply to user
                    // $result = $bot->replyText($event['replyToken'], $event['message']['text']);
                    
     
                    // or we can use replyMessage() instead to send reply message0
                    // $textMessageBuilder = new TextMessageBuilder($event['message']['text']);
                    // $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
     
                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                }
            }
        }
    }


});
 
$app->run();