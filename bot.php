<?php
include 'curl.php';
header("Content-Type: application/json");
date_default_timezone_set('Asia/Jakarta');
define('BOT_TOKEN', '');
define('CHAT_ID','');

function kirimTelegram($pesan) {
    $API = "https://api.telegram.org/bot".BOT_TOKEN."/sendmessage?chat_id=".CHAT_ID."&text=$pesan";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_URL, $API);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
 
function conHari($hari){ 
    $waktu          = strtotime(date('Y-m-d H:i:s'));
    $waktu_awal     = null;
    $waktu_akhir    = null;
    $status         = false;

    switch($hari){
     case 'Sun':
        $getHari        = "Minggu";
        break;
     case 'Mon': 
        $getHari        = "Senin";
        $waktu_awal     = strtotime(date("Y-m-d")."18:20:00");
        $waktu_akhir    = strtotime(date("Y-m-d")."18:40:00");
        break;
     case 'Tue':
        $getHari        = "Selasa";
        $waktu_awal     = strtotime(date("Y-m-d")."18:20:00");
        $waktu_akhir    = strtotime(date("Y-m-d")."18:40:00");
        break;
     case 'Wed':
        $getHari        = "Rabu";
        $waktu_awal     = strtotime(date("Y-m-d")."18:20:00");
        $waktu_akhir    = strtotime(date("Y-m-d")."18:40:00");
        break;
     case 'Thu':
        $getHari        = "Kamis";
        $waktu_awal     = strtotime(date("Y-m-d")."18:20:00");
        $waktu_akhir    = strtotime(date("Y-m-d")."18:40:00");
        break;
     case 'Fri':
        $getHari        = "Jumat"; 
        $waktu_awal     = strtotime(date("Y-m-d")."18:20:00");
        $waktu_akhir    = strtotime(date("Y-m-d")."18:40:00");
        break;
     case 'Sat':
        $getHari        = "Sabtu"; 
        $waktu_awal     = strtotime(date("Y-m-d")."16:20:00");
        $waktu_akhir    = strtotime(date("Y-m-d")."18:20:00");
        break;
     default:
        $getHari = "Salah"; 
        break;
    }

    if ($waktu >= $waktu_awal && $waktu <= $waktu_akhir)
        $status = true;

    

    $array = [
        "status_absensi"    => $status,
        "hari"              => $getHari,
        "jadwal"            => [
            "waktu_awal"=>date("H:i:s",$waktu_awal),
            "waktu_akhir"=>date("H:i:s",$waktu_akhir),
        ]
    ];
    
    return $array;
}

function absen($data){
    $cekWaktu       = conHari(date("D"));
    $user           = $data['username'];
    $pass           = $data['password'];
    $urlCookies     = "";
    $urlAbsensi     = "";
    $message        = null;
    $data           = null;
    if($cekWaktu['status_absensi']){

        $headers =  [
            "Host: ",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:80.0) Gecko/20100101 Firefox/80.0",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
            "Accept-Language: en-US,en;q=0.5",
            "Content-Type: application/x-www-form-urlencoded",
            "Origin: ",
            "Connection: keep-alive",
            "Referer: ",
        ];
        $post = "userid=$user&pin=$pass&login=Login";
        $curl = curl($urlCookies, '' . $post . '', $headers);
        $cookies = getcookies($curl);

        $headers =  [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:80.0) Gecko/20100101 Firefox/80.0",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
            "Accept-Language: en-US,en;q=0.5",
            "Referer: ",
            "Connection: keep-alive",
            "Cookie: PHPSESSID=".$cookies['PHPSESSID'],
            "Upgrade-Insecure-Requests: 1"
        ];
        $result = curl($urlAbsensi,'',$headers);
        // preg_match("/Sorry, you don't have any schedule for this time/", $result, $check);
        preg_match('/<img width="90" src="/', $result, $check);
    
        if($check){
            $pecah = explode('<img width="90" src="../foto/mahasiswa/" alt="FOTO"/>', $result);
            $pecah = explode('<td><span class="error">', $pecah[1]);
            $pecah = explode('</span>', $pecah[1]);

            $data =  [
                "presensi" =>  $pecah[0] == "Info" ? "Selamat Anda Berhasil Presensi :)" : $pecah[0],
            ];
        } else {
            $message = "login gagal";
        }
    } else {
        $message = "waktu tidak ada";
        $data    = $cekWaktu;
    }

    $array = [
        "status"    => $message == null ? true : false,
        "message"   => $message,
        "data"      => $data == null ? null : $data,
    ];

    $handle = fopen(dirname(__FILE__)."/log.txt", 'a');
    if($message == ""){
        $read_log = file_get_contents("log.txt");
        $check_log = preg_match("/".$array['data']['presensi']." SERVER ".date("Y-m-d")."/", $read_log);
        if($check_log == 0){
            kirimTelegram($array['data']['presensi']);
            fwrite($handle, $array['data']['presensi']." SERVER ".date("Y-m-d")." - (LOG ". date("Y-m-d H:i:s") . ")\n");
        }
    } else {
        fwrite($handle, "Tidak Ada Jadwal - ". date("Y-m-d H:i:s") . "\n");
    }

    return json_encode($array, JSON_PRETTY_PRINT);
}

if ($_GET['user'] != null and $_GET['pass'] != null) {
    $data = [
        "username" => $_GET['user'],
        "password" => $_GET['pass'],
    ];
    print_r(absen($data));
} else {
    $data = [
        "status" => false,
        "msg" => 'yang bener aa'
    ];
    print_r(json_encode($data, JSON_PRETTY_PRINT));
}
