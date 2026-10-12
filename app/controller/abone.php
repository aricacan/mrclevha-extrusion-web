<?php

// Bülten aboneliği. Kayıt subscribers tablosuna düşer, geldiği sayfaya dönülür.

$back = isset($_POST["donus"]) && $_POST["donus"] !== '' ? $_POST["donus"] : site_url();

if( strpos($back, URL) !== 0 ){
  $back = site_url();
}

if( !$_POST ){
  header("Location: ".$back);
  die;
}

if( !csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '') || request_throttled('newsletter_form') ){
  header("Location: ".$back.(strpos($back, '?') === false ? '?' : '&')."abone=hata#bulten");
  die;
}

$mail  = trim($_POST["abone"]);
$durum = 'ok';

if( $mail === '' ){
  $durum = 'bos';
}elseif( !filter_var($mail, FILTER_VALIDATE_EMAIL) ){
  $durum = 'mail';
}else{

  // Aynı adres ikinci kez eklenmez (uniq_mail); zaten kayıtlıysa bilgi verilir
  $exists = $conn->prepare("SELECT COUNT(*) FROM subscribers WHERE subscriber_mail=:mail");
  $exists->execute(array("mail" => $mail));

  if( (int)$exists->fetchColumn() > 0 ){
    $durum = 'kayitli';
  }else{

    $insert = $conn->prepare(
      "INSERT INTO subscribers SET subscriber_mail=:mail, subscriber_ip=:ip, subscriber_date=:date"
    );
    $ok = $insert->execute(array(
      "mail" => $mail,
      "ip"   => GetIP(),
      "date" => date("Y-m-d H:i:s")
    ));

    if( !$ok ){
      $durum = 'hata';
    }elseif( $settings["smtp_server"] && $settings["smtp_user"] ){

      $safeMail = htmlspecialchars($mail, ENT_QUOTES, 'UTF-8');

      sendMail(array(
        "mail"    => $mail,
        "subject" => $settings["site_name"]." — bültene abone oldunuz",
        "body"    => "<p>Bültenimize abone olduğunuz için teşekkür ederiz.</p>"
                   . "<p>Yeni ürünler, teknik föyler ve fuar duyurularını bu adrese göndereceğiz.</p>"
      ));

      if( $settings["admin_mail"] ){
        sendMail(array(
          "mail"    => $settings["admin_mail"],
          "subject" => "Yeni bülten abonesi",
          "body"    => "<p>Yeni abone: ".$safeMail."</p>"
        ));
      }
    }
  }
}

header("Location: ".$back.(strpos($back, '?') === false ? '?' : '&')."abone=".$durum."#bulten");
die;
