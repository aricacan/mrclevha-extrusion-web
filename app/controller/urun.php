<?php

if( !route(1) ){
    header("Location: /");
    die;
}elseif( route(1) ){

  $product = $conn->prepare("SELECT * FROM products WHERE product_url=:id ");
  $product-> execute(array("id"=>route(1) ));
  $product = $product->fetch(PDO::FETCH_ASSOC);
  localize_product($product);

  if( !$product ){
    header("Location: /");
    die;
  }

  $title       = $product["product_name"];
  $keywords    = $product["product_keyword"];
  $description = $product["product_desc"];
}

