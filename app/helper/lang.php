<?php

/**
 * Desteklenen diller. İlk sıradaki varsayılandır ve URL öneki almaz.
 */
function available_languages(){
  return ['tr', 'en'];
}

function default_language(){
  $languages = available_languages();
  return $languages[0];
}

function current_language(){
  global $lang;
  return in_array($lang, available_languages(), true) ? $lang : default_language();
}

/**
 * Aktif dilin sözlüğünü yükler. İlk çağrıda okunur, sonrasında bellekten döner.
 */
function lang_dictionary(){
  static $dictionaries = [];
  $language = current_language();

  if( !isset($dictionaries[$language]) ){
    $file = PATH.'/app/lang/'.$language.'.php';
    $dictionaries[$language] = file_exists($file) ? require $file : [];
  }

  return $dictionaries[$language];
}

/**
 * Çeviri anahtarını çözer. Anahtar yoksa anahtarın kendisi döner;
 * böylece eksik çeviri sayfayı bozmaz, gözle fark edilir.
 */
function t($key){
  $dictionary = lang_dictionary();
  return isset($dictionary[$key]) ? $dictionary[$key] : $key;
}

/**
 * Verilen yolu aktif dilin önekiyle üretir.
 * Varsayılan dil önek almaz: /iletisim  —  diğer diller alır: /en/iletisim
 */
function lang_url($path = '', $language = null){
  $language = $language ?: current_language();
  $path     = ltrim($path, '/');

  if( $language === default_language() ){
    return site_url($path);
  }

  return site_url($language.($path !== '' ? '/'.$path : ''));
}

/**
 * Aktif sayfanın başka bir dildeki karşılığı — dil değiştirici için.
 */
function lang_switch_url($language){
  global $route;
  $segments = is_array($route) ? array_values(array_filter($route, 'strlen')) : [];

  if( isset($segments[0]) && $segments[0] === 'home' ){
    $segments = [];
  }

  return lang_url(implode('/', $segments), $language);
}
