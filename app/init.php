<?php

require_once __DIR__.'/env.php';
load_env(__DIR__.'/../.env');

$cookieSecure = isset($_ENV['SESSION_COOKIE_SECURE'])
  ? $_ENV['SESSION_COOKIE_SECURE'] === '1'
  : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params(array(
  'lifetime' => 0,
  'path' => '/',
  'secure' => $cookieSecure,
  'httponly' => true,
  'samesite' => 'Lax'
));
session_start();
ob_start();

$config = require __DIR__.'/config.php';

try {
  $conn = new PDO("mysql:host=".$config["db"]["host"].";dbname=".$config["db"]["name"].";charset=".$config["db"]["charset"].";", $config["db"]["user"], $config["db"]["pass"], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (PDOException $e) {
  // Localhost socket hatası durumunda 127.0.0.1 ile otomatik fallback dene
  if ($config["db"]["host"] === 'localhost') {
    try {
      $conn = new PDO("mysql:host=127.0.0.1;dbname=".$config["db"]["name"].";charset=".$config["db"]["charset"].";", $config["db"]["user"], $config["db"]["pass"], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
      ]);
    } catch (PDOException $e2) {
      die("Veritabani Baglanti Hatasi: " . $e2->getMessage());
    }
  } else {
    die("Veritabani Baglanti Hatasi: " . $e->getMessage());
  }
}

// remember_* fonksiyonları burada, helper otomatik yüklemesinden önce
// gerekiyor — oturum bu noktada çerezden kurulur.
require __DIR__.'/remember.php';
require __DIR__.'/localize.php';

if( !empty($_SESSION["softyr_userlogin"]) ){

  // Zaten oturum açık, çereze bakmaya gerek yok.

}elseif( !empty($_COOKIE[REMEMBER_COOKIE]) ){

  $clientId = remember_consume();

  if( $clientId ){
    $row = $conn->prepare("SELECT * FROM clients WHERE client_id=:id");
    $row->execute(array("id" => $clientId));
    $row = $row->fetch(PDO::FETCH_ASSOC);

    if( $row ){
      $access = json_decode($row["access"], true);

      session_regenerate_id(true);

      $_SESSION["softyr_userlogin"] = 1;
      $_SESSION["softyr_userid"]    = $row["client_id"];
      $_SESSION["softyr_userpass"]  = $row["password"];

      if( !empty($access["admin_access"]) ){
        $_SESSION["softyr_adminlogin"] = 1;
      }
    }
  }

}

$settingsStmt = $conn->prepare("SELECT * FROM settings WHERE id=:id");
$settingsStmt->execute(array("id"=>1));
$settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
  die("Hata: settings tablosunda kayit bulunamadi! Lutfen phpMyAdmin uzerinden SQL kodunu calistirin.");
}

$loader   = new \Twig\Loader\FilesystemLoader(__DIR__.'/views');
$twig     = new \Twig\Environment($loader, ['autoescape' => 'html']);

$user = $conn->prepare("SELECT * FROM clients WHERE client_id=:id");
$user->execute(array("id"=>isset($_SESSION["softyr_userid"]) ? $_SESSION["softyr_userid"] : 0));
$user = $user->fetch(PDO::FETCH_ASSOC) ?: array();
$user['auth']     = !empty($_SESSION["softyr_userlogin"]);
$user["access"]   = !empty($user["access"]) ? json_decode($user["access"],true) : array();

foreach ( glob(__DIR__.'/helper/*.php') as $helper ) {
  require $helper;
}

foreach ( glob(__DIR__.'/classes/*.php') as $class ) {
  require $class;
}

// Şablonlarda çeviri ve dil bağlantıları
$twig->addFunction(new \Twig\TwigFunction('t', 't'));
$twig->addFunction(new \Twig\TwigFunction('lang_url', 'lang_url'));
$twig->addFunction(new \Twig\TwigFunction('lang_switch_url', 'lang_switch_url'));
$twig->addFunction(new \Twig\TwigFunction('current_language', 'current_language'));
$twig->addFunction(new \Twig\TwigFunction('available_languages', 'available_languages'));
$twig->addFunction(new \Twig\TwigFunction('csrf_token', 'csrf_token'));
