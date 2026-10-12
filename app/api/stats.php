<?php

/**
 * /api/stats — özet sayaçlar (ERP dashboard için). İş verisi hassas
 * sayılabileceğinden (toplam talep sayısı, abone sayısı vb.) API
 * anahtarı ister.
 */

api_require_auth();

$method = api_method();

if( $method !== 'GET' ){
  api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
}

function stats_count($conn, $sql){
  return (int)$conn->query($sql)->fetchColumn();
}

function stats_sum($conn, $sql){
  $value = $conn->query($sql)->fetchColumn();
  return $value !== null ? (int)$value : 0;
}

api_send(array('data' => array(
  'products'          => stats_count($conn, "SELECT COUNT(*) FROM products"),
  'categories'        => stats_count($conn, "SELECT COUNT(*) FROM categories"),
  'sectors'           => stats_count($conn, "SELECT COUNT(*) FROM pages WHERE page_type=1"),
  'pages'             => stats_count($conn, "SELECT COUNT(*) FROM pages WHERE page_type=2"),
  'blogPosts'         => stats_count($conn, "SELECT COUNT(*) FROM pages WHERE page_type=3"),
  'catalogs'          => stats_count($conn, "SELECT COUNT(*) FROM files WHERE file_type=1"),
  'galleryImages'     => stats_count($conn, "SELECT COUNT(*) FROM files WHERE file_type=2"),
  'tickets'           => stats_count($conn, "SELECT COUNT(*) FROM tickets"),
  'ticketsUnanswered' => stats_count($conn, "SELECT COUNT(*) FROM tickets WHERE reply_message IS NULL OR reply_message = ''"),
  'subscribers'       => stats_count($conn, "SELECT COUNT(*) FROM subscribers"),
  'totalProductViews' => stats_sum($conn, "SELECT SUM(product_views) FROM products"),
  'totalPageViews'    => stats_sum($conn, "SELECT SUM(page_views) FROM pages"),
  'totalCatalogDownloads' => stats_sum($conn, "SELECT SUM(file_downloads) FROM files WHERE file_type=1")
)));
