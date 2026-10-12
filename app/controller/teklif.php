<?php

// Teklif Al penceresinin gönderim adresi.
// Kayıt tickets tablosuna düşer, ardından geldiği sayfaya geri dönülür.

$back = isset($_POST["donus"]) && $_POST["donus"] !== '' ? $_POST["donus"] : site_url();

// Açık yönlendirme olmasın: yalnızca kendi sitemize dönebiliriz
if( strpos($back, URL) !== 0 ){
  $back = site_url();
}

if( !$_POST ){
  header("Location: ".$back);
  die;
}

if( !csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '') || request_throttled('quote_form') ){
  header("Location: ".$back.(strpos($back, '?') === false ? '?' : '&')."teklif=hata#teklif");
  die;
}

$qName    = trim($_POST["name"]);
$qPhone   = trim($_POST["telephone"]);
$qMail    = trim($_POST["email"]);
$qService = trim($_POST["service"]);

$durum = 'ok';

if( $qName === '' || $qPhone === '' || $qMail === '' ){
  $durum = 'bos';
}elseif( !filter_var($qMail, FILTER_VALIDATE_EMAIL) ){
  $durum = 'mail';
}else{

  $insert = $conn->prepare(
    "INSERT INTO tickets SET customer_name=:name, customer_email=:mail,
     customer_telephone=:phone, ticket_subject=:subject, ticket_message=:message, ticket_date=:date"
  );
  $ok = $insert->execute(array(
    "name"    => $qName,
    "mail"    => $qMail,
    "phone"   => $qPhone,
    "subject" => "Teklif Talebi — ".($qService ?: 'Genel'),
    "message" => "Teklif Al formundan gönderildi.\nİlgilenilen alan: ".($qService ?: 'Belirtilmedi'),
    "date"    => date("Y-m-d H:i:s")
  ));

  if( !$ok ){
    $durum = 'hata';
  }else{

    $ticket_id = $conn->lastInsertId();
    $safe = function($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };

    if( $settings["smtp_server"] && $settings["smtp_user"] ){

      $detay = "<p><strong>Ad Soyad:</strong> ".$safe($qName)."<br>"
             . "<strong>Telefon:</strong> ".$safe($qPhone)."<br>"
             . "<strong>E-posta:</strong> ".$safe($qMail)."<br>"
             . "<strong>İlgilenilen alan:</strong> ".$safe($qService ?: 'Belirtilmedi')."</p>";

      if( $settings["admin_mail"] ){
        sendMail(array(
          "mail"    => $settings["admin_mail"],
          "subject" => "Yeni teklif talebi #".$ticket_id,
          "body"    => $detay
        ));
      }

      sendMail(array(
        "mail"    => $qMail,
        "subject" => $settings["site_name"]." — teklif talebiniz alındı",
        "body"    => "<p>Merhaba ".$safe($qName).",</p>"
                   . "<p>Teklif talebiniz bize ulaştı. Teknik ekibimiz en kısa sürede sizinle iletişime geçecek.</p>"
                   . "<p><strong>Talep numaranız:</strong> #".$ticket_id."</p><hr>".$detay
      ));
    }
  }
}

header("Location: ".$back.(strpos($back, '?') === false ? '?' : '&')."teklif=".$durum."#teklif");
die;
