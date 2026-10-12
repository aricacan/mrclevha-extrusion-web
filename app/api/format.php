<?php

/**
 * Kaynaklar arası paylaşılan biçimlendirme/yardımcı fonksiyonlar.
 * Sadece fonksiyon tanımları içerir — çalıştırılabilir kod yok,
 * bu yüzden birden fazla kaynak dosyası güvenle require edebilir.
 */

function category_format($row){
  return array(
    'slug'          => $row['category_url'],
    'name'          => $row['category_name'],
    'nameEn'        => isset($row['category_name_en']) ? $row['category_name_en'] : '',
    'description'   => $row['category_desc'],
    'descriptionEn' => isset($row['category_desc_en']) ? $row['category_desc_en'] : '',
    'image'         => api_absolute_url($row['category_image']),
    'views'         => isset($row['category_views']) ? (int)$row['category_views'] : 0
  );
}

function category_id_from_slug($conn, $slug){
  if( !$slug ){
    return 0;
  }
  $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_url=:slug");
  $stmt->execute(array('slug' => $slug));
  $id = $stmt->fetchColumn();
  return $id ? (int)$id : 0;
}

function product_category_slug($conn, $categoryId){
  if( !$categoryId ){
    return null;
  }
  $stmt = $conn->prepare("SELECT category_url FROM categories WHERE category_id=:id");
  $stmt->execute(array('id' => $categoryId));
  $url = $stmt->fetchColumn();
  return $url ?: null;
}

function product_format($conn, $row){
  return array(
    'slug'          => $row['product_url'],
    'name'          => $row['product_name'],
    'nameEn'        => isset($row['product_name_en']) ? $row['product_name_en'] : '',
    'description'   => $row['product_desc'],
    'descriptionEn' => isset($row['product_desc_en']) ? $row['product_desc_en'] : '',
    'content'       => $row['product_content'],
    'contentEn'     => isset($row['product_content_en']) ? $row['product_content_en'] : '',
    'image'         => api_absolute_url($row['product_image']),
    'price'         => $row['product_price'] !== null ? (float)$row['product_price'] : null,
    'categorySlug'  => product_category_slug($conn, $row['product_category']),
    'views'         => isset($row['product_views']) ? (int)$row['product_views'] : 0
  );
}

function catalog_format($row){
  return array(
    'id'        => (int)$row['file_id'],
    'name'      => $row['file_name'],
    'url'       => api_absolute_url($row['file_url']),
    'downloads' => isset($row['file_downloads']) ? (int)$row['file_downloads'] : 0
  );
}

/** /api/sectors ve /api/pages ortak biçimlendirmesi (pages tablosu). */
function page_resource_format($row){
  return array(
    'slug'          => $row['page_get'],
    'name'          => $row['page_name'],
    'nameEn'        => isset($row['page_name_en']) ? $row['page_name_en'] : '',
    'description'   => $row['page_description'],
    'descriptionEn' => isset($row['page_description_en']) ? $row['page_description_en'] : '',
    'content'       => $row['page_content'],
    'contentEn'     => isset($row['page_content_en']) ? $row['page_content_en'] : '',
    'image'         => api_absolute_url($row['page_image']),
    'views'         => isset($row['page_views']) ? (int)$row['page_views'] : 0
  );
}

/** /api/blog — alan adları istenen sözleşmeye göre: title/excerpt/coverImage/publishedAt. */
function blog_format($row){
  return array(
    'slug'        => $row['page_get'],
    'title'       => $row['page_name'],
    'titleEn'     => isset($row['page_name_en']) ? $row['page_name_en'] : '',
    'excerpt'     => $row['page_description'],
    'excerptEn'   => isset($row['page_description_en']) ? $row['page_description_en'] : '',
    'content'     => $row['page_content'],
    'contentEn'   => isset($row['page_content_en']) ? $row['page_content_en'] : '',
    'coverImage'  => api_absolute_url($row['page_image']),
    'publishedAt' => api_iso_date($row['page_date']),
    'views'       => isset($row['page_views']) ? (int)$row['page_views'] : 0
  );
}

function ticket_format($row){
  return array(
    'id'          => (int)$row['ticket_id'],
    'name'        => $row['customer_name'],
    'email'       => $row['customer_email'],
    'telephone'   => $row['customer_telephone'],
    'subject'     => $row['ticket_subject'],
    'message'     => $row['ticket_message'],
    'reply'       => $row['reply_message'],
    'createdAt'   => api_iso_date($row['ticket_date'])
  );
}

function subscriber_format($row){
  return array(
    'id'        => (int)$row['subscriber_id'],
    'email'     => $row['subscriber_mail'],
    'createdAt' => api_iso_date($row['subscriber_date'])
  );
}
