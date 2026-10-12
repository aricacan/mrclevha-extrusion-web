<?php

/** /api/products — ürünler (products tablosu). */

$method = api_method();

if( $method === 'GET' && !$slug ){

  list($page, $perPage, $offset) = api_paginate_params();

  $where = '';
  $bind  = array();
  if( !empty($_GET['category']) ){
    $where = "WHERE product_category = :cat";
    $bind['cat'] = category_id_from_slug($conn, $_GET['category']);
  }

  $countStmt = $conn->prepare("SELECT COUNT(*) FROM products $where");
  $countStmt->execute($bind);
  $total = (int)$countStmt->fetchColumn();

  $stmt = $conn->prepare("SELECT * FROM products $where ORDER BY product_id DESC LIMIT :limit OFFSET :offset");
  foreach( $bind as $k => $v ){
    $stmt->bindValue(':'.$k, $v);
  }
  $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  api_send(array(
    'data' => array_map(function($r) use ($conn){ return product_format($conn, $r); }, $rows),
    'meta' => api_meta($page, $perPage, $total)
  ));

}elseif( $method === 'GET' && $slug ){

  $stmt = $conn->prepare("SELECT * FROM products WHERE product_url=:slug");
  $stmt->execute(array('slug' => $slug));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Ürün bulunamadı: '.$slug, 404, 'not_found');
  }

  api_bump_counter($conn, 'products', 'product_id', $row['product_id'], 'product_views');
  $row['product_views'] = (int)$row['product_views'] + 1;

  api_send(array('data' => product_format($conn, $row)));

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

  $exists = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_url=:slug");
  $exists->execute(array('slug' => $newSlug));
  if( $exists->fetchColumn() > 0 ){
    api_error('Bu slug zaten kullanılıyor: '.$newSlug, 409, 'slug_conflict');
  }

  $categoryId = !empty($body['categorySlug']) ? category_id_from_slug($conn, $body['categorySlug']) : 0;

  $insert = $conn->prepare(
    "INSERT INTO products SET product_name=:name, product_url=:slug, product_desc=:desc,
     product_content=:content, product_image=:image, product_price=:price, product_category=:cat,
     product_name_en=:nameEn, product_desc_en=:descEn, product_content_en=:contentEn"
  );
  $insert->execute(array(
    'name'       => $body['name'],
    'slug'       => $newSlug,
    'desc'       => isset($body['description']) ? $body['description'] : '',
    'content'    => isset($body['content']) ? $body['content'] : '',
    'image'      => isset($body['image']) ? $body['image'] : '',
    'price'      => isset($body['price']) ? (float)$body['price'] : 0,
    'cat'        => $categoryId,
    'nameEn'     => isset($body['nameEn']) ? $body['nameEn'] : '',
    'descEn'     => isset($body['descriptionEn']) ? $body['descriptionEn'] : '',
    'contentEn'  => isset($body['contentEn']) ? $body['contentEn'] : ''
  ));

  $stmt = $conn->prepare("SELECT * FROM products WHERE product_id=:id");
  $stmt->execute(array('id' => $conn->lastInsertId()));
  api_send(array('data' => product_format($conn, $stmt->fetch(PDO::FETCH_ASSOC))), 201);

}elseif( $method === 'PUT' && $slug ){

  api_require_auth();

  $stmt = $conn->prepare("SELECT * FROM products WHERE product_url=:slug");
  $stmt->execute(array('slug' => $slug));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Ürün bulunamadı: '.$slug, 404, 'not_found');
  }

  $body    = api_input();
  $newSlug = $slug;

  if( !empty($body['slug']) && api_slugify($body['slug']) !== $slug ){
    $newSlug = api_slugify($body['slug']);
    $exists  = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_url=:slug AND product_id != :id");
    $exists->execute(array('slug' => $newSlug, 'id' => $row['product_id']));
    if( $exists->fetchColumn() > 0 ){
      api_error('Bu slug zaten kullanılıyor: '.$newSlug, 409, 'slug_conflict');
    }
  }

  $categoryId = array_key_exists('categorySlug', $body)
    ? category_id_from_slug($conn, $body['categorySlug'])
    : $row['product_category'];

  $update = $conn->prepare(
    "UPDATE products SET product_name=:name, product_url=:slug, product_desc=:desc,
     product_content=:content, product_image=:image, product_price=:price, product_category=:cat,
     product_name_en=:nameEn, product_desc_en=:descEn, product_content_en=:contentEn
     WHERE product_id=:id"
  );
  $update->execute(array(
    'name'      => array_key_exists('name', $body) ? $body['name'] : $row['product_name'],
    'slug'      => $newSlug,
    'desc'      => array_key_exists('description', $body) ? $body['description'] : $row['product_desc'],
    'content'   => array_key_exists('content', $body) ? $body['content'] : $row['product_content'],
    'image'     => array_key_exists('image', $body) ? $body['image'] : $row['product_image'],
    'price'     => array_key_exists('price', $body) ? (float)$body['price'] : $row['product_price'],
    'cat'       => $categoryId,
    'nameEn'    => array_key_exists('nameEn', $body) ? $body['nameEn'] : $row['product_name_en'],
    'descEn'    => array_key_exists('descriptionEn', $body) ? $body['descriptionEn'] : $row['product_desc_en'],
    'contentEn' => array_key_exists('contentEn', $body) ? $body['contentEn'] : $row['product_content_en'],
    'id'        => $row['product_id']
  ));

  $stmt = $conn->prepare("SELECT * FROM products WHERE product_id=:id");
  $stmt->execute(array('id' => $row['product_id']));
  api_send(array('data' => product_format($conn, $stmt->fetch(PDO::FETCH_ASSOC))));

}elseif( $method === 'DELETE' && $slug ){

  api_require_auth();

  $stmt = $conn->prepare("SELECT product_id FROM products WHERE product_url=:slug");
  $stmt->execute(array('slug' => $slug));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Ürün bulunamadı: '.$slug, 404, 'not_found');
  }

  $conn->prepare("DELETE FROM products WHERE product_id=:id")->execute(array('id' => $row['product_id']));
  api_send(array('data' => array('deleted' => true)));

}else{
  api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
}
