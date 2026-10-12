<?php

// Tüm sektörler — hem liste görünümü hem de detaydaki geçiş şeridi için
$sectorList = $conn->prepare(
  "SELECT * FROM pages WHERE page_type=:type ORDER BY page_id ASC"
);
$sectorList->execute(array("type" => 1));
$sectorList = $sectorList->fetchAll(PDO::FETCH_ASSOC);
localize_pages($sectorList);

if( !$sectorList ){
  header("Location: /");
  die;
}

$page = null;

// route(1) varsa o sektörün detayı, yoksa liste görünümü
if( route(1) ){
  foreach( $sectorList as $row ){
    if( $row["page_get"] === route(1) ){
      $page = $row;
      break;
    }
  }

  // Bilinmeyen sektör slug'ı → liste görünümüne düş
  if( !$page ){
    header("Location: ".site_url('hizmetler'));
    die;
  }
}

if( $page ){
  $title       = $page["page_name"];
  $keywords    = $page["page_keywords"];
  $description = $page["page_description"];
} else {
  $title       = t('sectors_index_title').' — '.$settings["site_name"];
  $keywords    = $settings["site_keywords"];
  $description = $settings["site_description"];
}

// Detay sayfasında "diğer sektörler" için
$otherSectors = array();
foreach( $sectorList as $row ){
  if( !$page || $row["page_get"] !== $page["page_get"] ){
    $otherSectors[] = $row;
  }
}
