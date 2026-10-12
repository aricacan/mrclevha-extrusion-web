<?php

// Kurumsal sayfalar tek bir sayfada alt alta sunulur; her bölümün kendi URL'i
// vardır ve o URL ile girildiğinde ilgili bölüm aktif olur.
$corporatePages = $conn->prepare(
  "SELECT * FROM pages WHERE page_type=:type ORDER BY page_id ASC"
);
$corporatePages->execute(array("type" => 2));
$corporatePages = $corporatePages->fetchAll(PDO::FETCH_ASSOC);
localize_pages($corporatePages);

if( !$corporatePages ){
  header("Location: /");
  die;
}

// URL'deki bölüm; belirtilmemişse ilk bölüm
$activeSlug = route(1) ?: $corporatePages[0]["page_get"];
$page       = null;

foreach( $corporatePages as $row ){
  if( $row["page_get"] === $activeSlug ){
    $page = $row;
    break;
  }
}

// Bilinmeyen slug ile gelinirse ilk bölüme düş
if( !$page ){
  $page       = $corporatePages[0];
  $activeSlug = $page["page_get"];
}

$title       = $page["page_name"];
$keywords    = $page["page_keywords"];
$description = $page["page_description"];
