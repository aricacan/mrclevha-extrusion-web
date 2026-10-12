<?php

$title       = t('contact_title');
$keywords    = $settings["site_keywords"];
$description = $settings["site_description"];

// Asistan panelinden veya bültenden gelen ön dolgu
$form_name      = '';
$form_email     = isset($_GET["abone"]) ? trim($_GET["abone"]) : '';
$form_telephone = '';
$form_subject   = '';
$form_message   = isset($_GET["mesaj"]) ? trim($_GET["mesaj"]) : '';

if( $_POST ){

  if( !csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '') || request_throttled('contact_form') ){
    $error = 1;
    $errorText = t('form_err_save');
    return;
  }

  $form_name      = trim($_POST["name"]);
  $form_email     = trim($_POST["email"]);
  $form_telephone = trim($_POST["telephone"]);
  $form_subject   = trim($_POST["subject"]);
  $form_message   = trim($_POST["message"]);

  $captchaOk = true;

  if( $settings["recaptcha"] == 2 ){
    $captchaAnswer = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
    $verify        = cURL(
      "https://www.google.com/recaptcha/api/siteverify?secret=".$settings["recaptcha_secret"].
      "&response=".urlencode($captchaAnswer)."&remoteip=".$_SERVER['REMOTE_ADDR']
    );
    $verify    = json_decode($verify);
    $captchaOk = ($verify && $verify->success);
  }

  if( !$captchaOk ){
    $error     = 1;
    $errorText = t('form_err_captcha');

  }elseif( $form_name === '' || $form_email === '' || $form_telephone === '' ||
           $form_subject === '' || $form_message === '' ){
    $error     = 1;
    $errorText = t('form_err_empty');

  }elseif( !filter_var($form_email, FILTER_VALIDATE_EMAIL) ){
    $error     = 1;
    $errorText = t('form_err_email');

  }elseif( mb_strlen(str_replace(' ', '', $form_message)) < 10 ){
    $error     = 1;
    $errorText = t('form_err_short');

  }else{

    $insert = $conn->prepare(
      "INSERT INTO tickets SET customer_name=:customer_name, customer_email=:customer_email,
       customer_telephone=:customer_telephone, ticket_subject=:ticket_subject,
       ticket_message=:ticket_message, ticket_date=:date"
    );
    $insert = $insert->execute(array(
      "customer_name"      => $form_name,
      "customer_email"     => $form_email,
      "customer_telephone" => $form_telephone,
      "ticket_subject"     => $form_subject,
      "ticket_message"     => $form_message,
      "date"               => date("Y-m-d H:i:s")
    ));

    if( $insert ){

      $ticket_id   = $conn->lastInsertId();
      $successText = t('form_ok');

      // Kayıt formda kalmasın
      $form_name = $form_email = $form_telephone = $form_subject = $form_message = '';

      // SMTP tanımlıysa bildirim gönder
      if( $settings["smtp_server"] && $settings["smtp_user"] ){

        $safe = function($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };

        $detay = "<p><strong>Ad Soyad:</strong> ".$safe($form_name ?: $_POST["name"])."<br>"
               . "<strong>E-posta:</strong> ".$safe($_POST["email"])."<br>"
               . "<strong>Telefon:</strong> ".$safe($_POST["telephone"])."<br>"
               . "<strong>Konu:</strong> ".$safe($_POST["subject"])."</p>"
               . "<p>".nl2br($safe($_POST["message"]))."</p>";

        // Yöneticiye
        if( $settings["admin_mail"] ){
          sendMail(array(
            "mail"    => $settings["admin_mail"],
            "subject" => "Yeni iletişim mesajı #".$ticket_id." — ".$_POST["subject"],
            "body"    => $detay
          ));
        }

        // Gönderene otomatik teyit
        sendMail(array(
          "mail"    => $_POST["email"],
          "subject" => $settings["site_name"]." — mesajınızı aldık",
          "body"    => "<p>Merhaba ".$safe($_POST["name"]).",</p>"
                     . "<p>Mesajınız bize ulaştı, en kısa sürede dönüş yapacağız.</p>"
                     . "<p><strong>Talep numaranız:</strong> #".$ticket_id."</p>"
                     . "<hr>".$detay
        ));
      }

      // SMS bildirimi. SMSUser yalnızca bir sağlayıcı seçiliyse tanımlanır;
      // seçili değilken çağırmak ölümcül hataya yol açıyordu.
      if( $settings["alert_newticket"] == 2 && in_array($settings["alert_type"], array(1, 3))
          && function_exists('SMSUser') && $settings["admin_telephone"] ){
        SMSUser($settings["admin_telephone"], "Websitenizde #".$ticket_id." idli yeni bir iletişim mesajı mevcut.");
      }

    }else{
      $error     = 1;
      $errorText = t('form_err_save');
    }
  }
}
