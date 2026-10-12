<?php

/**
 * "Beni hatırla" — selector/validator token deseni.
 *
 * Eski yöntem çerezde ham parola hash'ini taşıyordu: çerez çalınırsa
 * (XSS, paylaşımlı bilgisayar, tarayıcı geçmişi sızıntısı) saldırgan
 * kalıcı, süresiz yetkili erişim elde ediyordu. Bu yöntemde çerez
 * yalnızca rastgele, tek kullanımlık bir token taşır; veritabanındaki
 * karşılığı olmadan hiçbir işe yaramaz ve her kullanımda yenilenir.
 */

define('REMEMBER_COOKIE', 'remember_token');
define('REMEMBER_DAYS', 30);

function remember_issue($clientId){
  global $conn;

  $selector  = bin2hex(random_bytes(12));
  $validator = bin2hex(random_bytes(32));
  $hash      = hash('sha256', $validator);
  $expires   = date('Y-m-d H:i:s', time() + REMEMBER_DAYS * 86400);

  $insert = $conn->prepare(
    "INSERT INTO remember_tokens SET client_id=:client_id, selector=:selector,
     token_hash=:hash, expires_at=:expires, created_at=:created"
  );
  $insert->execute(array(
    "client_id" => $clientId,
    "selector"  => $selector,
    "hash"      => $hash,
    "expires"   => $expires,
    "created"   => date('Y-m-d H:i:s')
  ));

  setcookie(
    REMEMBER_COOKIE,
    $selector.'.'.$validator,
    time() + REMEMBER_DAYS * 86400,
    '/', '', true, true
  );
}

/**
 * Çerezdeki token'ı doğrular. Geçerliyse client_id döner ve token'ı
 * yeniler (rotate) — aynı çerezin ikinci kez kullanılması engellenir,
 * her istek kendi tek kullanımlık token'ını taşır.
 * Geçersiz/eşleşmeyen bir selector bulunursa, olası çalıntı token
 * ihtimaline karşı o selector'a ait kayıt tamamen silinir.
 */
function remember_consume(){
  global $conn;

  if( empty($_COOKIE[REMEMBER_COOKIE]) || strpos($_COOKIE[REMEMBER_COOKIE], '.') === false ){
    return false;
  }

  list($selector, $validator) = explode('.', $_COOKIE[REMEMBER_COOKIE], 2);

  $row = $conn->prepare("SELECT * FROM remember_tokens WHERE selector=:selector");
  $row->execute(array("selector" => $selector));
  $row = $row->fetch(PDO::FETCH_ASSOC);

  remember_clear_cookie();

  if( !$row ){
    return false;
  }

  $delete = $conn->prepare("DELETE FROM remember_tokens WHERE selector=:selector");
  $delete->execute(array("selector" => $selector));

  if( strtotime($row["expires_at"]) < time() ){
    return false;
  }

  if( !hash_equals($row["token_hash"], hash('sha256', $validator)) ){
    return false;
  }

  remember_issue($row["client_id"]);

  return $row["client_id"];
}

/** Oturumu kapatırken çağrılır: çerezdeki token'ı ve DB kaydını siler. */
function remember_forget(){
  global $conn;

  if( !empty($_COOKIE[REMEMBER_COOKIE]) && strpos($_COOKIE[REMEMBER_COOKIE], '.') !== false ){
    list($selector, ) = explode('.', $_COOKIE[REMEMBER_COOKIE], 2);
    $delete = $conn->prepare("DELETE FROM remember_tokens WHERE selector=:selector");
    $delete->execute(array("selector" => $selector));
  }

  remember_clear_cookie();
}

function remember_clear_cookie(){
  setcookie(REMEMBER_COOKIE, '', time() - 3600, '/', '', true, true);
  unset($_COOKIE[REMEMBER_COOKIE]);
}
