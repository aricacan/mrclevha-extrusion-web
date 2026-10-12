<?php

/**
 * /api/subscribers — bülten aboneleri (subscribers tablosu).
 * E-posta adresi kişisel veri olduğu için TÜM metodlar (GET dahil)
 * API anahtarı ister.
 */

api_require_auth();

$method = api_method();
$id     = $slug; // router'dan gelen ikinci segment sayısal id'dir

if( $method === 'GET' && !$id ){

  list($page, $perPage, $offset) = api_paginate_params();

  $total = (int)$conn->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();

  $stmt = $conn->prepare("SELECT * FROM subscribers ORDER BY subscriber_date DESC LIMIT :limit OFFSET :offset");
  $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  api_send(array(
    'data' => array_map('subscriber_format', $rows),
    'meta' => api_meta($page, $perPage, $total)
  ));

}elseif( $method === 'POST' && !$id ){

  $body = api_input();

  if( empty($body['email']) || !filter_var($body['email'], FILTER_VALIDATE_EMAIL) ){
    api_error('Geçerli bir email alanı zorunludur.', 422, 'validation_error');
  }

  $exists = $conn->prepare("SELECT subscriber_id FROM subscribers WHERE subscriber_mail=:mail");
  $exists->execute(array('mail' => $body['email']));
  $existingId = $exists->fetchColumn();

  if( !$existingId ){
    $conn->prepare("INSERT INTO subscribers SET subscriber_mail=:mail")->execute(array('mail' => $body['email']));
    $existingId = $conn->lastInsertId();
  }

  $stmt = $conn->prepare("SELECT * FROM subscribers WHERE subscriber_id=:id");
  $stmt->execute(array('id' => $existingId));
  api_send(array('data' => subscriber_format($stmt->fetch(PDO::FETCH_ASSOC))), 201);

}elseif( $method === 'DELETE' && $id ){

  $stmt = $conn->prepare("SELECT subscriber_id FROM subscribers WHERE subscriber_id=:id");
  $stmt->execute(array('id' => (int)$id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Abone bulunamadı: '.$id, 404, 'not_found');
  }

  $conn->prepare("DELETE FROM subscribers WHERE subscriber_id=:id")->execute(array('id' => $row['subscriber_id']));
  api_send(array('data' => array('deleted' => true)));

}else{
  api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
}
