<?php

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

$title       = $query !== '' ? $query.' — '.t('search_title') : t('search_title');
$keywords    = $settings["site_keywords"];
$description = $settings["site_description"];

$searchPages = [];
$searchFiles = [];

// En az 2 karakter — tek harfle tüm tabloyu taramanın anlamı yok
if( mb_strlen($query) >= 2 ){

  $like = '%'.$query.'%';

  $searchPages = $conn->prepare(
    "SELECT page_name, page_get, page_type, page_description, page_name_en, page_description_en
       FROM pages
      WHERE page_name LIKE :q1 OR page_description LIKE :q2 OR page_content LIKE :q3
         OR page_name_en LIKE :q4 OR page_description_en LIKE :q5 OR page_content_en LIKE :q6
      ORDER BY page_type ASC
      LIMIT 30"
  );
  $searchPages->execute(array("q1"=>$like, "q2"=>$like, "q3"=>$like, "q4"=>$like, "q5"=>$like, "q6"=>$like));
  $searchPages = $searchPages->fetchAll(PDO::FETCH_ASSOC);
  localize_rows($searchPages, array(
    'page_name'        => 'page_name_en',
    'page_description' => 'page_description_en'
  ));

  $searchFiles = $conn->prepare(
    "SELECT file_name, file_url FROM files WHERE file_type=1 AND file_name LIKE :q LIMIT 20"
  );
  $searchFiles->execute(array("q"=>$like));
  $searchFiles = $searchFiles->fetchAll(PDO::FETCH_ASSOC);
}
