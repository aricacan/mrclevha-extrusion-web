<?php

/** /api/categories — ürün aileleri (categories tablosu). */

$method = api_method();

if( $method === 'GET' && !$slug ){

  list($page, $perPage, $offset) = api_paginate_params();

  $total = (int)$conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();

  $stmt = $conn->prepare("SELECT * FROM categories ORDER BY category_id ASC LIMIT :limit OFFSET :offset");
  $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  api_send(array(
    'data' => array_map('category_format', $rows),
    'meta' => api_meta($page, $perPage, $total)
  ));

}elseif( $method === 'GET' && $slug ){

  $stmt = $conn->prepare("SELECT * FROM categories WHERE category_url=:slug");
  $stmt->execute(array('slug' => $slug));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Kategori bulunamadı: '.$slug, 404, 'not_found');
  }

  api_bump_counter($conn, 'categories', 'category_id', $row['category_id'], 'category_views');
  $row['category_views'] = (int)$row['category_views'] + 1;

  api_send(array('data' => category_format($row)));

}elseif( $method === 'POST' && !$slug ){

  api_require_auth();
  $body = api_input();

  if( empty($body['name']) ){
    api_error('name alanı zorunludur.', 422, 'validation_error');
  }

  $newSlug = !empty($body['slug']) ? api_slugify($body['slug']) : api_slugify($body['name']);
  if( $newSlug === '' ){
    api_error('Geçerli bir slug üretilemedi.', 422, 'validation_error');
  }

  $exists = $conn->prepare("SELECT COUNT(*) FROM categories WHERE category_url=:slug");
  $exists->execute(array('slug' => $newSlug));
  if( $exists->fetchColumn() > 0 ){
    api_error('Bu slug zaten kullanılıyor: '.$newSlug, 409, 'slug_conflict');
  }

  $insert = $conn->prepare(
    "INSERT INTO categories SET category_name=:name, category_url=:slug, category_desc=:desc, category_image=:image,
     category_name_en=:nameEn, category_desc_en=:descEn"
  );
  $insert->execute(array(
    'name'    => $body['name'],
    'slug'    => $newSlug,
    'desc'    => isset($body['description']) ? $body['description'] : '',
    'image'   => isset($body['image']) ? $body['image'] : '',
    'nameEn'  => isset($body['nameEn']) ? $body['nameEn'] : '',
    'descEn'  => isset($body['descriptionEn']) ? $body['descriptionEn'] : ''
  ));

  $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id=:id");
  $stmt->execute(array('id' => $conn->lastInsertId()));
  api_send(array('data' => category_format($stmt->fetch(PDO::FETCH_ASSOC))), 201);

}elseif( $method === 'PUT' && $slug ){

  api_require_auth();

  $stmt = $conn->prepare("SELECT * FROM categories WHERE category_url=:slug");
  $stmt->execute(array('slug' => $slug));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Kategori bulunamadı: '.$slug, 404, 'not_found');
  }

  $body    = api_input();
  $newSlug = $slug;

  if( !empty($body['slug']) && api_slugify($body['slug']) !== $slug ){
    $newSlug = api_slugify($body['slug']);
    $exists  = $conn->prepare("SELECT COUNT(*) FROM categories WHERE category_url=:slug AND category_id != :id");
    $exists->execute(array('slug' => $newSlug, 'id' => $row['category_id']));
    if( $exists->fetchColumn() > 0 ){
      api_error('Bu slug zaten kullanılıyor: '.$newSlug, 409, 'slug_conflict');
    }
  }

  $update = $conn->prepare(
    "UPDATE categories SET category_name=:name, category_url=:slug, category_desc=:desc, category_image=:image,
     category_name_en=:nameEn, category_desc_en=:descEn WHERE category_id=:id"
  );
  $update->execute(array(
    'name'   => array_key_exists('name', $body) ? $body['name'] : $row['category_name'],
    'slug'   => $newSlug,
    'desc'   => array_key_exists('description', $body) ? $body['description'] : $row['category_desc'],
    'image'  => array_key_exists('image', $body) ? $body['image'] : $row['category_image'],
    'nameEn' => array_key_exists('nameEn', $body) ? $body['nameEn'] : $row['category_name_en'],
    'descEn' => array_key_exists('descriptionEn', $body) ? $body['descriptionEn'] : $row['category_desc_en'],
    'id'     => $row['category_id']
  ));

  $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id=:id");
  $stmt->execute(array('id' => $row['category_id']));
  api_send(array('data' => category_format($stmt->fetch(PDO::FETCH_ASSOC))));

}elseif( $method === 'DELETE' && $slug ){

  api_require_auth();

  $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_url=:slug");
  $stmt->execute(array('slug' => $slug));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Kategori bulunamadı: '.$slug, 404, 'not_found');
  }

  $inUse = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_category=:id");
  $inUse->execute(array('id' => $row['category_id']));
  if( $inUse->fetchColumn() > 0 && empty($_GET['force']) ){
    api_error('Bu kategoriye bağlı ürünler var. Silmek için ?force=1 ekleyin (ürünlerin kategorisi boşa düşer).', 409, 'category_in_use');
  }

  if( !empty($_GET['force']) ){
    $conn->prepare("UPDATE products SET product_category=0 WHERE product_category=:id")->execute(array('id' => $row['category_id']));
  }

  $conn->prepare("DELETE FROM categories WHERE category_id=:id")->execute(array('id' => $row['category_id']));
  api_send(array('data' => array('deleted' => true)));

}else{
  api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
}
