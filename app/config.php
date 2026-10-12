<?php

define('PATH', realpath('.'));
define('SUBFOLDER', false);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', PATH.'/php-error.log');
date_default_timezone_set('Europe/Istanbul');

require_once __DIR__.'/env.php';
load_env(__DIR__.'/../.env');

// Ortama göre site adresi.
// .env'de APP_URL tanımlıysa (production'da her zaman öyle) sabit o kullanılır.
// Tanımlı değilse (yerel Docker) isteğin geldiği host'tan dinamik üretilir —
// böylece hem "localhost:8090" hem de bir tünel/başka domain üzerinden
// erişimde (ör. önizleme paylaşımı) tüm CSS/JS/görsel yolları doğru üretilir.
// Hiçbir istek bağlamı yoksa (CLI/cron) production adresine düşer.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

define('URL', isset($_ENV['APP_URL']) && $_ENV['APP_URL'] !== ''
  ? rtrim($_ENV['APP_URL'], '/')
  : (isset($_SERVER['HTTP_HOST'])
      ? ($isHttps ? 'https://' : 'http://').$_SERVER['HTTP_HOST']
      : 'https://mrclevha.com'));

// Search engines must not index staging environments. Production can opt in
// explicitly with INDEXABLE=1 in .env.
$configuredHost = parse_url(URL, PHP_URL_HOST);
define('INDEXABLE', isset($_ENV['INDEXABLE'])
  ? $_ENV['INDEXABLE'] === '1'
  : !preg_match('/^(test|staging|dev)([-.])?/i', (string)$configuredHost));

// CSS/JS önbellek damgası. .htaccess bu dosyaları 1 hafta önbelleğe aldığı için,
// güncelleme sonrası tarayıcıların eski sürümü servis etmesini bu engeller.
// Yerelde her istekte değişir; canlıda dosya yüklendikten sonra elle artırılır.
define('ASSET_VERSION', isset($_ENV['ASSET_VERSION']) && $_ENV['ASSET_VERSION'] !== ''
  ? $_ENV['ASSET_VERSION']
  : '1');

return [
  'db' => [
    'name'    =>  $_ENV['DB_NAME'] ?? 'mrclevha2',
    'host'    =>  $_ENV['DB_HOST'] ?? 'localhost',
    'user'    =>  $_ENV['DB_USER'] ?? 'mrclevha2',
    'pass'    =>  $_ENV['DB_PASS'] ?? '',
    'charset' =>  $_ENV['DB_CHARSET'] ?? 'utf8mb4'
  ]
];
