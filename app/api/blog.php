<?php

/**
 * /api/blog — duyurular (pages, page_type=3).
 * Alan adları istenen sözleşmeye göre: slug, title, excerpt, content,
 * coverImage, publishedAt. Biçimlendirme fonksiyonu (blog_format) /api/all
 * tarafından da kullanıldığı için format.php'de tanımlı.
 */

$method = api_method();

if( $method === 'GET' && !$slug ){

  list($page, $perPage, $offset) = api_paginate_params();

  $total = (int)$conn->query("SELECT COUNT(*) FROM pages WHERE page_type=3")->fetchColumn();

  $stmt = $conn->prepare("SELECT * FROM pages WHERE page_type=3 ORDER BY page_date DESC LIMIT :limit OFFSET :offset");
  $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  api_send(array(
    'data' => array_map('blog_format', $rows),
    'meta' => api_meta($page, $perPage, $total)
  ));

}elseif( $method === 'GET' && $slug ){

  $stmt = $conn->prepare("SELECT * FROM pages WHERE page_type=3 AND page_get=:slug");
  $stmt->execute(array('slug' => $slug));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if( !$row ){
    api_error('Yazı bulunamadı: '.$slug, 404, 'not_found');
  }

  api_bump_counter($conn, 'pages', 'page_id', $row['page_id'], 'page_views');
  $row['page_views'] = (int)$row['page_views'] + 1;

  api_send(array('data' => blog_format($row)));

}elseif( $method === 'POST' && !$slug ){

  api_require_auth();
  $body = api_input();

  if( empty($body['title']) ){
    api_error('title alanı zorunludur.', 422, 'validation_error');
  }

  $newSlug = !empty($body['slug']) ? api_slugify($body['slug']) : api_slugify($body['title']);
  if( $newSlug === '' ){
    api_error('Geçerli bir slug üretilemedi.', 422, 'validation_error');
  }

  $exists = $conn->prepare("SELECT COUNT(*) FROM pages WHERE page_get=:slug");
  $exists->execute(array('slug' => $newSlug));
  if( $exists->fetchColumn() > 0 ){
    api_error('Bu slug zaten kullanılıyor: '.$newSlug, 409, 'slug_conflict');
  }

  $publishedAt = !empty($body['publishedAt']) && strtotime($body['publishedAt'])
    ? date('Y-m-d H:i:s', strtotime($body['publishedAt']))
    : date('Y-m-d H:i:s');

  $insert = $conn->prepare(
    "INSERT INTO pages SET page_name=:name, page_get=:slug, page_description=:excerpt,
     page_content=:content, page_image=:image, page_type=3, page_date=:date,
     page_name_en=:nameEn, page_description_en=:excerptEn, page_content_en=:contentEn"
  );
  $insert->execute(array(
    'name'       => $body['title'],
    'slug'       => $newSlug,
    'excerpt'    => isset($body['excerpt']) ? $body['excerpt'] : '',
    'content'    => isset($body['content']) ? $body['content'] : '',
    'image'      => isset($body['coverImage']) ? $body['coverImage'] : '',
    'date'       => $publishedAt,
    'nameEn'     => isset($body['titleEn']) ? $body['titleEn'] : '',
    'excerptEn'  => isset($body['excerptEn']) ? $body['excerptEn'] : '',
    'contentEn'  => isset($body['contentEn']) ? $body['contentEn'] : ''
  ));

  $stmt = $conn->prepare("SELECT * FROM pages WHERE page_id=:id");
  $stmt->execute(array('id' => $conn->lastInsertId()));
  api_send(array('data' => blog_format($stmt->fetch(PDO::FETCH_ASSOC))), 201);

}elseif( $method === 'PUT' && $slug ){

  api_require_auth();

  $stmt = $conn->prepare("SELECT * FROM pages WHERE page_type=3 AND page_get=:slug");
  $stmt->execute(array('slug' => $slug));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Yazı bulunamadı: '.$slug, 404, 'not_found');
  }

  $body    = api_input();
  $newSlug = $slug;

  if( !empty($body['slug']) && api_slugify($body['slug']) !== $slug ){
    $newSlug = api_slugify($body['slug']);
    $exists  = $conn->prepare("SELECT COUNT(*) FROM pages WHERE page_get=:slug AND page_id != :id");
    $exists->execute(array('slug' => $newSlug, 'id' => $row['page_id']));
    if( $exists->fetchColumn() > 0 ){
      api_error('Bu slug zaten kullanılıyor: '.$newSlug, 409, 'slug_conflict');
    }
  }

  $publishedAt = !empty($body['publishedAt']) && strtotime($body['publishedAt'])
    ? date('Y-m-d H:i:s', strtotime($body['publishedAt']))
    : $row['page_date'];

  $update = $conn->prepare(
    "UPDATE pages SET page_name=:name, page_get=:slug, page_description=:excerpt,
     page_content=:content, page_image=:image, page_date=:date,
     page_name_en=:nameEn, page_description_en=:excerptEn, page_content_en=:contentEn
     WHERE page_id=:id"
  );
  $update->execute(array(
    'name'      => array_key_exists('title', $body) ? $body['title'] : $row['page_name'],
    'slug'      => $newSlug,
    'excerpt'   => array_key_exists('excerpt', $body) ? $body['excerpt'] : $row['page_description'],
    'content'   => array_key_exists('content', $body) ? $body['content'] : $row['page_content'],
    'image'     => array_key_exists('coverImage', $body) ? $body['coverImage'] : $row['page_image'],
    'date'      => $publishedAt,
    'nameEn'    => array_key_exists('titleEn', $body) ? $body['titleEn'] : $row['page_name_en'],
    'excerptEn' => array_key_exists('excerptEn', $body) ? $body['excerptEn'] : $row['page_description_en'],
    'contentEn' => array_key_exists('contentEn', $body) ? $body['contentEn'] : $row['page_content_en'],
    'id'        => $row['page_id']
  ));

  $stmt = $conn->prepare("SELECT * FROM pages WHERE page_id=:id");
  $stmt->execute(array('id' => $row['page_id']));
  api_send(array('data' => blog_format($stmt->fetch(PDO::FETCH_ASSOC))));

}elseif( $method === 'DELETE' && $slug ){

  api_require_auth();

  $stmt = $conn->prepare("SELECT page_id FROM pages WHERE page_type=3 AND page_get=:slug");
  $stmt->execute(array('slug' => $slug));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if( !$row ){
    api_error('Yazı bulunamadı: '.$slug, 404, 'not_found');
  }

  $conn->prepare("DELETE FROM pages WHERE page_id=:id")->execute(array('id' => $row['page_id']));
  api_send(array('data' => array('deleted' => true)));

}else{
  api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
}
