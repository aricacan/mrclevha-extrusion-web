<?php

$legalPages = array(
  'gizlilik' => array(
    'title' => 'Gizlilik Politikası',
    'heading' => 'Gizlilik Politikası',
    'intro' => 'MRC Levha, ziyaretçilerin kişisel verilerinin korunmasına ve güvenli işlenmesine önem verir.',
    'sections' => array(
      array('title' => 'Toplanan bilgiler', 'body' => 'İletişim ve teklif formlarında paylaştığınız ad, e-posta, telefon ve mesaj bilgileri; talebinize yanıt vermek ve hizmet sunmak amacıyla işlenebilir.'),
      array('title' => 'Kullanım amacı ve saklama', 'body' => 'Veriler yalnızca iletişim, teklif, destek ve yasal yükümlülüklerin yerine getirilmesi amaçlarıyla kullanılır. Gerekli sürenin sonunda silinir veya anonimleştirilir.'),
      array('title' => 'Çerezler', 'body' => 'Site, oturum ve güvenlik için zorunlu çerezler kullanır. Zorunlu olmayan pazarlama veya analiz çerezleri, açık rıza olmadan çalıştırılmaz.'),
      array('title' => 'Haklarınız ve iletişim', 'body' => 'Kişisel verilerinizle ilgili talepleriniz için '.$settings['site_mail'].' adresinden veya '.$settings['site_address'].' adresinden bize ulaşabilirsiniz.')
    )
  ),
  'kvkk' => array(
    'title' => 'KVKK Aydınlatma Metni',
    'heading' => 'KVKK Aydınlatma Metni',
    'intro' => 'Bu metin, 6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında bilgilendirme amacıyla hazırlanmıştır.',
    'sections' => array(
      array('title' => 'Veri sorumlusu', 'body' => $settings['site_name'].' — '.$settings['site_address'].' adresinde faaliyet gösteren veri sorumlusudur.'),
      array('title' => 'İşleme amaçları', 'body' => 'İletişim taleplerinin yanıtlanması, teklif süreçlerinin yürütülmesi, bilgi güvenliği ve mevzuattan doğan yükümlülüklerin yerine getirilmesi.'),
      array('title' => 'Aktarım ve güvenlik', 'body' => 'Veriler, yalnızca hizmetin gerektirdiği ölçüde ve kanunen yetkili kişi veya kurumlarla paylaşılır; erişim ve saklama süreçlerinde teknik ve idari tedbirler uygulanır.'),
      array('title' => 'Başvuru', 'body' => 'KVKK kapsamındaki başvurularınızı '.$settings['site_mail'].' adresine iletebilirsiniz. Başvurular yürürlükteki mevzuattaki usul ve süreler içinde yanıtlanır.')
    )
  ),
  'kullanim-sartlari' => array(
    'title' => 'Kullanım Şartları',
    'heading' => 'Kullanım Şartları',
    'intro' => 'Bu web sitesini kullanarak aşağıdaki şartları kabul etmiş olursunuz.',
    'sections' => array(
      array('title' => 'İçerik ve fikri mülkiyet', 'body' => 'Sitedeki metin, görsel, logo ve diğer içerikler MRC Levha veya ilgili hak sahiplerine aittir. Yazılı izin olmadan ticari amaçla kopyalanamaz veya yayımlanamaz.'),
      array('title' => 'Ürün bilgileri', 'body' => 'Sitedeki bilgiler genel bilgilendirme içindir. Teknik özellikler, termin ve fiyatlar teklif veya sözleşme sırasında kesinleşir.'),
      array('title' => 'Form kullanımı', 'body' => 'Formlarda doğru ve güncel bilgi vermeniz gerekir. Kötüye kullanım, otomatik istek, yetkisiz erişim ve site güvenliğini tehdit eden işlemler yasaktır.'),
      array('title' => 'İletişim', 'body' => 'Sorularınız için '.$settings['site_mail'].' adresinden veya '.$settings['site_telephone'].' numarasından bize ulaşabilirsiniz.')
    )
  )
);

$legalPage = isset($legalPages[route(0)]) ? $legalPages[route(0)] : $legalPages['gizlilik'];
$title = $legalPage['title'];
$keywords = $settings['site_keywords'];
$description = $legalPage['intro'];
$templateDir = 'legal';
