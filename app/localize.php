<?php

/**
 * DB satırlarına İngilizce içerik bindirir.
 *
 * Sayfalar/kategoriler/ürünler/slider gibi tablolarda her alanın bir de
 * "_en" karşılığı var (ör. page_name / page_name_en). Aktif dil İngilizce
 * ise ve o alanın İngilizce karşılığı doluysa, ana alanın üzerine yazılır
 * — böylece Twig şablonları hiç değişmeden (her zaman "page_name" gibi
 * temel alan adını okuyarak) doğru dildeki içeriği gösterir.
 *
 * İngilizce çevirisi girilmemiş satırlarda otomatik olarak Türkçe içerik
 * kalır — eksik çeviri sayfayı boş bırakmaz.
 */
function localize_row(&$row, $fieldMap){
  if( !$row || current_language() === default_language() ){
    return;
  }
  foreach( $fieldMap as $base => $enField ){
    if( !empty($row[$enField]) ){
      $row[$base] = $row[$enField];
    }
  }
}

function localize_rows(&$rows, $fieldMap){
  if( !$rows || current_language() === default_language() ){
    return;
  }
  foreach( $rows as &$row ){
    localize_row($row, $fieldMap);
  }
  unset($row);
}

/** Tablo bazlı standart alan eşlemeleri — tekrar yazmamak için. */
function localize_pages(&$rows){
  localize_rows($rows, array(
    'page_name'        => 'page_name_en',
    'page_description' => 'page_description_en',
    'page_content'      => 'page_content_en'
  ));
}

function localize_category(&$row){
  localize_row($row, array(
    'category_name' => 'category_name_en',
    'category_desc' => 'category_desc_en'
  ));
}

function localize_categories(&$rows){
  localize_rows($rows, array(
    'category_name' => 'category_name_en',
    'category_desc' => 'category_desc_en'
  ));
}

function localize_product(&$row){
  localize_row($row, array(
    'product_name'    => 'product_name_en',
    'product_desc'    => 'product_desc_en',
    'product_content' => 'product_content_en'
  ));
}

function localize_products(&$rows){
  localize_rows($rows, array(
    'product_name'    => 'product_name_en',
    'product_desc'    => 'product_desc_en',
    'product_content' => 'product_content_en'
  ));
}

function localize_slider(&$rows){
  localize_rows($rows, array(
    'slider_name'    => 'slider_name_en',
    'slider_content' => 'slider_content_en',
    'slider_button'  => 'slider_button_en'
  ));
}
