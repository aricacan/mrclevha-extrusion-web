<?php

/**
 * /api/catalogs — katalog/teknik föy PDF'leri (files tablosu, file_type=1).
 * Bu kaynak numarik ID kullanır (slug değil), çünkü files tablosunda
 * benzersiz bir metin anahtarı yok.
 */

$method = api_method();
$id     = $slug; // router'dan gelen ikinci segment burada sayısal id'dir

if( $method === 'GET' && !$id ){

  list($page, $perPage, $offset) = api_paginate_params();

  $total = (int)$conn->query("SELECT COUNT(*) FROM files WHERE file_type=1")->fetchColumn();

  $stmt = $conn->prepare("SELECT * FROM files WHERE file_type=1 ORDER BY file_id DESC LIMIT :limit OFFSET :offset");
  $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  api_send(array(
    'data' => array_map('catalog_format', $rows),
    'meta' => api_meta($page, $perPage, $total)
  ));

}elseif( $method === 'GET' && $id ){

  $stmt = $conn->prepare("SELECT * FROM files WHERE file_type=1 AND file_id=:id");
  $stmt->execute(array('id' => (int)$id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Katalog bulunamadı: '.$id, 404, 'not_found');
  }

  // Tekil GET = kullanıcı katalog detayına/indirmeye tıkladı sayılır.
  api_bump_counter($conn, 'files', 'file_id', $row['file_id'], 'file_downloads');
  $row['file_downloads'] = (int)$row['file_downloads'] + 1;

  api_send(array('data' => catalog_format($row)));

}elseif( $method === 'POST' && !$id ){

  api_require_auth();
  $body = api_input();

  if( empty($body['name']) || empty($body['url']) ){
    api_error('name ve url alanları zorunludur.', 422, 'validation_error');
  }

  $insert = $conn->prepare("INSERT INTO files SET file_name=:name, file_url=:url, file_type=1");
  $insert->execute(array('name' => $body['name'], 'url' => $body['url']));

  $stmt = $conn->prepare("SELECT * FROM files WHERE file_id=:id");
  $stmt->execute(array('id' => $conn->lastInsertId()));
  api_send(array('data' => catalog_format($stmt->fetch(PDO::FETCH_ASSOC))), 201);

}elseif( $method === 'PUT' && $id ){

  api_require_auth();

  $stmt = $conn->prepare("SELECT * FROM files WHERE file_type=1 AND file_id=:id");
  $stmt->execute(array('id' => (int)$id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Katalog bulunamadı: '.$id, 404, 'not_found');
  }

  $body = api_input();

  $update = $conn->prepare("UPDATE files SET file_name=:name, file_url=:url WHERE file_id=:id");
  $update->execute(array(
    'name' => array_key_exists('name', $body) ? $body['name'] : $row['file_name'],
    'url'  => array_key_exists('url', $body) ? $body['url'] : $row['file_url'],
    'id'   => $row['file_id']
  ));

  $stmt = $conn->prepare("SELECT * FROM files WHERE file_id=:id");
  $stmt->execute(array('id' => $row['file_id']));
  api_send(array('data' => catalog_format($stmt->fetch(PDO::FETCH_ASSOC))));

}elseif( $method === 'DELETE' && $id ){

  api_require_auth();

  $stmt = $conn->prepare("SELECT file_id FROM files WHERE file_type=1 AND file_id=:id");
  $stmt->execute(array('id' => (int)$id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Katalog bulunamadı: '.$id, 404, 'not_found');
  }

  $conn->prepare("DELETE FROM files WHERE file_id=:id")->execute(array('id' => $row['file_id']));
  api_send(array('data' => array('deleted' => true)));

}else{
  api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
}
