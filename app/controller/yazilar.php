<?php

$newsList = $conn->prepare(
  "SELECT * FROM pages WHERE page_type=:type ORDER BY page_date DESC, page_id DESC"
);
$newsList->execute(array("type" => 3));
$newsList = $newsList->fetchAll(PDO::FETCH_ASSOC);
localize_pages($newsList);

$page = null;

if( route(1) ){
  $templateDir = "yazi_detay";

  foreach( $newsList as $row ){
    if( $row["page_get"] === route(1) ){
      $page = $row;
      break;
    }
  }

  // Bilinmeyen duyuru → listeye dön
  if( !$page ){
    header("Location: ".site_url('yazilar'));
    die;
  }

  $title       = $page["page_name"];
  $keywords    = $page["page_keywords"];
  $description = $page["page_description"];

  // Detayda "diğer duyurular" için
  $otherNews = array();
  foreach( $newsList as $row ){
    if( $row["page_get"] !== $page["page_get"] ){
      $otherNews[] = $row;
    }
  }
  $otherNews = array_slice($otherNews, 0, 3);

} else {
  $title       = t('news_index_title');
  $keywords    = $settings["site_keywords"];
  $description = $settings["site_description"];
}
