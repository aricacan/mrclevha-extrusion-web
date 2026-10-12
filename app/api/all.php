<?php

/**
 * /api/all — tüm kamuya açık içeriği TEK istekte döner (ürünler, kategoriler,
 * sektörler, kurumsal sayfalar, duyurular, kataloglar). ERP ilk/tam
 * senkronizasyon için tasarlandı — sayfalama yoktur, tüm kayıtlar döner.
 *
 * tickets/subscribers buraya dahil DEĞİLDİR (kişisel veri, ayrı auth'lu
 * uçlardan çekilmelidir). Bu uç görüntülenme sayaçlarını ARTIRMAZ — toplu
 * senkronizasyon "kullanıcı ilgisi" sayılmaz.
 */

$method = api_method();

if( $method !== 'GET' ){
  api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
}

$products = $conn->query("SELECT * FROM products ORDER BY product_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY category_id ASC")->fetchAll(PDO::FETCH_ASSOC);
$sectors = $conn->prepare("SELECT * FROM pages WHERE page_type=1 ORDER BY page_id ASC");
$sectors->execute();
$sectors = $sectors->fetchAll(PDO::FETCH_ASSOC);

$pages = $conn->prepare("SELECT * FROM pages WHERE page_type=2 ORDER BY page_id ASC");
$pages->execute();
$pages = $pages->fetchAll(PDO::FETCH_ASSOC);

$blog = $conn->prepare("SELECT * FROM pages WHERE page_type=3 ORDER BY page_date DESC");
$blog->execute();
$blog = $blog->fetchAll(PDO::FETCH_ASSOC);

$catalogs = $conn->prepare("SELECT * FROM files WHERE file_type=1 ORDER BY file_id DESC");
$catalogs->execute();
$catalogs = $catalogs->fetchAll(PDO::FETCH_ASSOC);

api_send(array(
  'data' => array(
    'products'   => array_map(function($r) use ($conn){ return product_format($conn, $r); }, $products),
    'categories' => array_map('category_format', $categories),
    'sectors'    => array_map('page_resource_format', $sectors),
    'pages'      => array_map('page_resource_format', $pages),
    'blog'       => array_map('blog_format', $blog),
    'catalogs'   => array_map('catalog_format', $catalogs)
  ),
  'meta' => array(
    'counts' => array(
      'products'   => count($products),
      'categories' => count($categories),
      'sectors'    => count($sectors),
      'pages'      => count($pages),
      'blog'       => count($blog),
      'catalogs'   => count($catalogs)
    )
  )
));
