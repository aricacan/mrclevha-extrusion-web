<?php

function controller($controllerName){
  $controllerName = strtolower($controllerName);
  return PATH.'/app/controller/'.$controllerName.'.php';
}

function view($viewName){
  $viewName = strtolower($viewName);
  return PATH.'/app/views/'.$viewName;
}

function route($index){
  global $route;
  if( isset($route[$index]) ){
    return $route[$index];
  }else{
    return false;
  }
}

function site_url($url = false){
  return URL.'/'.$url;
}

function GetIP(){
  if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
  $ip = getenv("HTTP_CLIENT_IP");
  else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
  $ip = getenv("HTTP_X_FORWARDED_FOR");
  else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
  $ip = getenv("REMOTE_ADDR");
  else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
  $ip = $_SERVER['REMOTE_ADDR'];
  else
  $ip = "unknown";
  return($ip);
}

function csrf_token(){
  if( empty($_SESSION['csrf_token']) ){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function csrf_valid($token){
  return is_string($token) && !empty($_SESSION['csrf_token'])
    && hash_equals($_SESSION['csrf_token'], $token);
}

function request_throttled($key, $seconds = 10){
  $now = time();
  $last = isset($_SESSION['rate_limit'][$key]) ? (int)$_SESSION['rate_limit'][$key] : 0;
  if( $last && ($now - $last) < $seconds ){
    return true;
  }
  $_SESSION['rate_limit'][$key] = $now;
  return false;
}
