<?php

/**
 * JSON API — ortak yardımcılar.
 * Bu dosya app/api/router.php tarafından tek seferde yüklenir.
 */

function api_send($data, $status = 200){
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  exit;
}

function api_error($message, $status = 400, $code = 'error'){
  api_send(array('error' => array('code' => $code, 'message' => $message)), $status);
}

/** POST/PUT gövdesini JSON olarak okur. Boşsa boş dizi döner. */
function api_input(){
  $raw = file_get_contents('php://input');
  if( $raw === '' || $raw === false ){
    return array();
  }
  $data = json_decode($raw, true);
  if( json_last_error() !== JSON_ERROR_NONE ){
    api_error('Geçersiz JSON gövdesi: '.json_last_error_msg(), 400, 'invalid_json');
  }
  return is_array($data) ? $data : array();
}

/**
 * Yazma uçları X-Api-Key başlığıyla korunur. Anahtar admin panelinde
 * Ayarlar sayfasında görüntülenir/yenilenir (settings.api_key).
 * Okuma uçları herkese açıktır — bu API'nin ayrı bir alan adından
 * (ör. cappafe.com) tüketilmesi hedeflenir.
 */
function api_authenticated(){
  global $settings;

  $key = null;
  if( isset($_SERVER['HTTP_X_API_KEY']) ){
    $key = $_SERVER['HTTP_X_API_KEY'];
  }elseif( isset($_GET['api_key']) ){
    $key = $_GET['api_key'];
  }

  return $key && !empty($settings['api_key']) && hash_equals($settings['api_key'], $key);
}

function api_require_auth(){
  api_require_https();

  if( !api_authenticated() ){
    api_error('Geçersiz veya eksik API anahtarı. X-Api-Key başlığı gerekli.', 401, 'unauthorized');
  }
}

/**
 * Yazma uçlarında API anahtarı düz metin başlıkla taşınır; HTTPS zorunlu
 * kılınmazsa ağ üzerinde çalınabilir. Yerel geliştirmede (localhost/
 * 127.0.0.1, veya APP_URL http:// ise) bu kontrol atlanır.
 */
function api_require_https(){
  if( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ){
    return;
  }
  if( !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ){
    return;
  }

  $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
  $isLocal = (strpos($host, 'localhost') !== false) || (strpos($host, '127.0.0.1') !== false);
  if( $isLocal || stripos(URL, 'https://') !== 0 ){
    return;
  }

  api_error('Yazma uçları yalnızca HTTPS üzerinden kullanılabilir.', 400, 'https_required');
}

function api_method(){
  return $_SERVER['REQUEST_METHOD'];
}

/** Göreli dosya yolunu (uploads/x.png) siteye göre tam URL'e çevirir. */
function api_absolute_url($path){
  if( !$path ){
    return null;
  }
  if( preg_match('#^https?://#i', $path) ){
    return $path;
  }
  return URL.'/'.ltrim($path, '/');
}

/** ISO 8601 tarih string'i üretir. Geçersiz/boş girdide null döner. */
function api_iso_date($value){
  if( !$value ){
    return null;
  }
  $ts = strtotime($value);
  return $ts ? date(DATE_ATOM, $ts) : null;
}

/** Türkçe karakterleri sadeleştirip URL-uyumlu slug üretir. */
function api_slugify($text){
  $tr = array('ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç');
  $en = array('s','s','i','i','g','g','u','u','o','o','c','c');
  $text = str_replace($tr, $en, (string)$text);
  $text = mb_strtolower($text, 'UTF-8');
  $text = preg_replace('/[^a-z0-9]+/', '-', $text);
  return trim($text, '-');
}

function api_paginate_params(){
  $page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
  $perPage = isset($_GET['perPage']) ? min(50, max(1, (int)$_GET['perPage'])) : 20;
  return array($page, $perPage, ($page - 1) * $perPage);
}

function api_meta($page, $perPage, $total){
  return array(
    'page'       => $page,
    'perPage'    => $perPage,
    'total'      => $total,
    'totalPages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0
  );
}

/**
 * Tekil kayıt görüntülemede (GET /kaynak/{id}) sayaç artırır — ürün/kategori/
 * sayfa görüntülenme sayısı, katalog indirme tıklaması gibi. $table ve
 * $counterCol sabit, kod içinden geldiği için enjeksiyon riski yok.
 */
function api_bump_counter($conn, $table, $idCol, $idValue, $counterCol){
  $sql = "UPDATE `$table` SET `$counterCol` = `$counterCol` + 1 WHERE `$idCol` = :id";
  $conn->prepare($sql)->execute(array('id' => $idValue));
}
