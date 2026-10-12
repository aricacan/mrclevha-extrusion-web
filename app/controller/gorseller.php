<?php


  $title       = t('nav_production');
  $keywords    = $settings["site_keywords"];
  $description = $settings["site_description"];


// file_type: 2 = fotoğraf, 3 = video — ikisi de üretim hattı galerisinde
$fileList = $conn->prepare("SELECT * FROM files WHERE file_type IN (2, 3) ORDER BY file_type, file_id");
$fileList->execute();
$fileList = $fileList->fetchAll(PDO::FETCH_ASSOC);
