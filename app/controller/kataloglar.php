<?php


  $title       = t('nav_catalogs');
  $keywords    = $settings["site_keywords"];
  $description = $settings["site_description"];


$fileList = $conn->prepare("SELECT * FROM files WHERE file_type=:type");
$fileList->execute(array("type"=>1));
$fileList = $fileList->fetchAll(PDO::FETCH_ASSOC);
