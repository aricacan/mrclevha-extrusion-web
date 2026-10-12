<?php

/**
 * /api/sectors ve /api/pages ortak gövdesi — ikisi de `pages` tablosunda
 * farklı bir page_type değeriyle tutulur, CRUD akışları birebir aynı.
 * Çağıran dosya (sectors.php / pages.php) sadece $pageType ve etiketi verir.
 */
function pages_resource_handle($conn, $pageType, $notFoundLabel, $slug){

  $method = api_method();

  if( $method === 'GET' && !$slug ){

    list($page, $perPage, $offset) = api_paginate_params();

    $countStmt = $conn->prepare("SELECT COUNT(*) FROM pages WHERE page_type=:type");
    $countStmt->execute(array('type' => $pageType));
    $total = (int)$countStmt->fetchColumn();

    $stmt = $conn->prepare("SELECT * FROM pages WHERE page_type=:type ORDER BY page_id ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':type', $pageType, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    api_send(array(
      'data' => array_map('page_resource_format', $rows),
      'meta' => api_meta($page, $perPage, $total)
    ));

  }elseif( $method === 'GET' && $slug ){

    $stmt = $conn->prepare("SELECT * FROM pages WHERE page_type=:type AND page_get=:slug");
    $stmt->execute(array('type' => $pageType, 'slug' => $slug));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if( !$row ){
      api_error($notFoundLabel.' bulunamadı: '.$slug, 404, 'not_found');
    }

    api_bump_counter($conn, 'pages', 'page_id', $row['page_id'], 'page_views');
    $row['page_views'] = (int)$row['page_views'] + 1;

    api_send(array('data' => page_resource_format($row)));

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

    $exists = $conn->prepare("SELECT COUNT(*) FROM pages WHERE page_get=:slug");
    $exists->execute(array('slug' => $newSlug));
    if( $exists->fetchColumn() > 0 ){
      api_error('Bu slug zaten kullanılıyor: '.$newSlug, 409, 'slug_conflict');
    }

    $insert = $conn->prepare(
      "INSERT INTO pages SET page_name=:name, page_get=:slug, page_description=:desc,
       page_content=:content, page_image=:image, page_type=:type,
       page_name_en=:nameEn, page_description_en=:descEn, page_content_en=:contentEn"
    );
    $insert->execute(array(
      'name'      => $body['name'],
      'slug'      => $newSlug,
      'desc'      => isset($body['description']) ? $body['description'] : '',
      'content'   => isset($body['content']) ? $body['content'] : '',
      'image'     => isset($body['image']) ? $body['image'] : '',
      'type'      => $pageType,
      'nameEn'    => isset($body['nameEn']) ? $body['nameEn'] : '',
      'descEn'    => isset($body['descriptionEn']) ? $body['descriptionEn'] : '',
      'contentEn' => isset($body['contentEn']) ? $body['contentEn'] : ''
    ));

    $stmt = $conn->prepare("SELECT * FROM pages WHERE page_id=:id");
    $stmt->execute(array('id' => $conn->lastInsertId()));
    api_send(array('data' => page_resource_format($stmt->fetch(PDO::FETCH_ASSOC))), 201);

  }elseif( $method === 'PUT' && $slug ){

    api_require_auth();

    $stmt = $conn->prepare("SELECT * FROM pages WHERE page_type=:type AND page_get=:slug");
    $stmt->execute(array('type' => $pageType, 'slug' => $slug));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if( !$row ){
      api_error($notFoundLabel.' bulunamadı: '.$slug, 404, 'not_found');
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

    $update = $conn->prepare(
      "UPDATE pages SET page_name=:name, page_get=:slug, page_description=:desc,
       page_content=:content, page_image=:image,
       page_name_en=:nameEn, page_description_en=:descEn, page_content_en=:contentEn
       WHERE page_id=:id"
    );
    $update->execute(array(
      'name'      => array_key_exists('name', $body) ? $body['name'] : $row['page_name'],
      'slug'      => $newSlug,
      'desc'      => array_key_exists('description', $body) ? $body['description'] : $row['page_description'],
      'content'   => array_key_exists('content', $body) ? $body['content'] : $row['page_content'],
      'image'     => array_key_exists('image', $body) ? $body['image'] : $row['page_image'],
      'nameEn'    => array_key_exists('nameEn', $body) ? $body['nameEn'] : $row['page_name_en'],
      'descEn'    => array_key_exists('descriptionEn', $body) ? $body['descriptionEn'] : $row['page_description_en'],
      'contentEn' => array_key_exists('contentEn', $body) ? $body['contentEn'] : $row['page_content_en'],
      'id'        => $row['page_id']
    ));

    $stmt = $conn->prepare("SELECT * FROM pages WHERE page_id=:id");
    $stmt->execute(array('id' => $row['page_id']));
    api_send(array('data' => page_resource_format($stmt->fetch(PDO::FETCH_ASSOC))));

  }elseif( $method === 'DELETE' && $slug ){

    api_require_auth();

    $stmt = $conn->prepare("SELECT page_id FROM pages WHERE page_type=:type AND page_get=:slug");
    $stmt->execute(array('type' => $pageType, 'slug' => $slug));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if( !$row ){
      api_error($notFoundLabel.' bulunamadı: '.$slug, 404, 'not_found');
    }

    $conn->prepare("DELETE FROM pages WHERE page_id=:id")->execute(array('id' => $row['page_id']));
    api_send(array('data' => array('deleted' => true)));

  }else{
    api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
  }
}
