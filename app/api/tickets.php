<?php

/**
 * /api/tickets — iletişim/teklif formu kayıtları (tickets tablosu).
 * Müşteri kişisel verisi içerdiği için TÜM metodlar (GET dahil) API
 * anahtarı ister — diğer kaynaklardan farkı budur.
 */

api_require_auth();

$method = api_method();
$id     = $slug; // router'dan gelen ikinci segment sayısal id'dir

if( $method === 'GET' && !$id ){

  list($page, $perPage, $offset) = api_paginate_params();

  $total = (int)$conn->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

  $stmt = $conn->prepare("SELECT * FROM tickets ORDER BY ticket_date DESC LIMIT :limit OFFSET :offset");
  $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  api_send(array(
    'data' => array_map('ticket_format', $rows),
    'meta' => api_meta($page, $perPage, $total)
  ));

}elseif( $method === 'GET' && $id ){

  $stmt = $conn->prepare("SELECT * FROM tickets WHERE ticket_id=:id");
  $stmt->execute(array('id' => (int)$id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Talep bulunamadı: '.$id, 404, 'not_found');
  }
  api_send(array('data' => ticket_format($row)));

}elseif( $method === 'POST' && !$id ){

  $body = api_input();

  if( empty($body['name']) || empty($body['message']) ){
    api_error('name ve message alanları zorunludur.', 422, 'validation_error');
  }

  $insert = $conn->prepare(
    "INSERT INTO tickets SET customer_name=:name, customer_email=:email, customer_telephone=:tel,
     ticket_subject=:subject, ticket_message=:message"
  );
  $insert->execute(array(
    'name'    => $body['name'],
    'email'   => isset($body['email']) ? $body['email'] : '',
    'tel'     => isset($body['telephone']) ? $body['telephone'] : '',
    'subject' => isset($body['subject']) ? $body['subject'] : '',
    'message' => $body['message']
  ));

  $stmt = $conn->prepare("SELECT * FROM tickets WHERE ticket_id=:id");
  $stmt->execute(array('id' => $conn->lastInsertId()));
  api_send(array('data' => ticket_format($stmt->fetch(PDO::FETCH_ASSOC))), 201);

}elseif( $method === 'PUT' && $id ){

  $stmt = $conn->prepare("SELECT * FROM tickets WHERE ticket_id=:id");
  $stmt->execute(array('id' => (int)$id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Talep bulunamadı: '.$id, 404, 'not_found');
  }

  $body = api_input();

  $update = $conn->prepare("UPDATE tickets SET reply_message=:reply WHERE ticket_id=:id");
  $update->execute(array(
    'reply' => array_key_exists('reply', $body) ? $body['reply'] : $row['reply_message'],
    'id'    => $row['ticket_id']
  ));

  $stmt = $conn->prepare("SELECT * FROM tickets WHERE ticket_id=:id");
  $stmt->execute(array('id' => $row['ticket_id']));
  api_send(array('data' => ticket_format($stmt->fetch(PDO::FETCH_ASSOC))));

}elseif( $method === 'DELETE' && $id ){

  $stmt = $conn->prepare("SELECT ticket_id FROM tickets WHERE ticket_id=:id");
  $stmt->execute(array('id' => (int)$id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Talep bulunamadı: '.$id, 404, 'not_found');
  }

  $conn->prepare("DELETE FROM tickets WHERE ticket_id=:id")->execute(array('id' => $row['ticket_id']));
  api_send(array('data' => array('deleted' => true)));

}else{
  api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
}
