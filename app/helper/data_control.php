<?php

function permalink($deger) { 

$turkce=array("ş","Ş","ı","(",")","","ü","Ü","ö","Ö","ç","Ç"," ","/","*","?","ş","Ş","ı","ğ","Ğ","İ","ö","Ö","Ç","ç","ü","Ü"); 
$duzgun=array("s","S","i","","","","u","U","o","O","c","C","-","-","-","","s","S","i","g","G","I","o","O","C","c","u","U"); 
$deger=str_replace($turkce,$duzgun,$deger);
$deger = preg_replace("@[^A-Za-z0-9-_]+@i","",$deger);
$deger = mb_strtolower($deger);

return $deger; } 

function currency($value){
  switch ($value) {
      case 'TRY':
        return '₺';
        break;
      case 'USD':
        return '$';
        break;
      case 'EUR':
        return '€';
        break;
   }
}

function product_type($value){
  switch ($value) {
      case '1':
        return 'Fiziksel Ürün';
        break;
      case '2':
        return 'Dijital Ürün';
        break;
   }    
}

function status($value){
  switch ($value) {
      case '1':
        return 'Sipariş Alındı';
        break;
      case '2':
        return 'Sipariş Hazırlanıyor';
        break;
      case '3':
        return 'Üretimde';
        break;
      case '4':
        return 'Kargoya Verildi';
        break;
      case '5':
        return 'Teslim Edildi';
        break;
      case '6':
        return 'İptal/İade Edildi';
        break;        
   }
}


function username_check($username){
  if (preg_match('/^[a-z\d_]{4,32}$/i', $username)){
    $validate = true;
  }else{
    $validate = false;
  }
  return $validate;
}

function email_check($email){
  if( filter_var($email,FILTER_VALIDATE_EMAIL) ){
    $validate = true;
  }else{
    $validate = false;
  }
  return $validate;
}

function userdata_check($where,$data){
  global $conn;
  $row  = $conn->prepare("SELECT * FROM clients WHERE $where=:data ");
  $row-> execute(array("data"=>$data));
  if( $row->rowCount() ){
    $validate = true;
  }else{
    $validate = false;
  }
  return $validate;
}

function userlogin_check($username, $pass){
  global $conn;
  $row  = $conn->prepare("SELECT * FROM clients WHERE username=:username");
  $row->execute(array("username" => $username));
  $user = $row->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    return false;
  }

  $stored = $user["password"];
  $legacyHash = md5(sha1(md5($pass)));

  if (password_verify($pass, $stored)) {
    return $user;
  }

  if (hash_equals($stored, $legacyHash)) {
    $newHash = password_hash($pass, PASSWORD_BCRYPT);
    $update = $conn->prepare("UPDATE clients SET password=:password WHERE client_id=:id");
    $update->execute(array("password" => $newHash, "id" => $user["client_id"]));
    $user["password"] = $newHash;
    return $user;
  }

  return false;
}

function countRow($data){
  global $conn;
    $where    = "";
    if( $data["where"] ):
      $where    = "WHERE ";
        foreach ($data["where"] as $key => $value) {
          $where.=" $key=:$key && ";
          $execute[$key]=$value;
        }
        $where    = substr($where,0,-3);
    else:
      $execute[]= "";
    endif;
  $row  = $conn->prepare("SELECT * FROM {$data['table']} $where ");
  $row-> execute($execute);
  $validate = $row->rowCount();
  return $validate;
}

function getRows($data){
  global $conn;
    $where    = "";
    $order    = "";
    $order    = "";
    $limit    = "";
    $execute[]= "";
    if( $data["where"] ):
      $where    = "WHERE ";
        foreach ($data["where"] as $key => $value) {
          $where.=" $key=:$key && ";
          $execute[$key]=$value;
        }
        $where    = substr($where,0,-3);
    endif;

    if( $data["order"] ): $order  = "ORDER BY ".$data["order"]." ".$data["order_type"]; endif;
    if( $data["limit"] ): $limit  = "LIMIT ".$data["limit"]; endif;
  $row  = $conn->prepare("SELECT * FROM {$data['table']} $where $order $limit ");
  $row-> execute($execute);
    if( $row->rowCount() ):
      $rows = $row->fetchAll(PDO::FETCH_ASSOC);
    else:
      $rows = [];
    endif;
  return $rows;
}

function getRow($data){
  global $conn;
    $where    = "WHERE ";
      foreach ($data["where"] as $key => $value) {
        $where.=" $key=:$key && ";
        $execute[$key]=$value;
      }
    $where    = substr($where,0,-3);
  $row  = $conn->prepare("SELECT * FROM {$data['table']} $where ");
  $row-> execute($execute);
    if( $row->rowCount() ):
      $row = $row->fetch(PDO::FETCH_ASSOC);
    else:
      $row = [];
    endif;
  return $row;
}
