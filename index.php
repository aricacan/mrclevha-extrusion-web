<?php
require __DIR__.'/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer;
require __DIR__.'/app/init.php';


$first_route  = explode('?',$_SERVER["REQUEST_URI"]);

  // Query string — URL'de "?" yoksa bu blok hiç çalışmaz
  if( isset($first_route[1]) && $first_route[1] !== '' ){
    foreach( explode('&',$first_route[1]) as $get ){
      $get = explode('=',$get,2);
      if( $get[0] !== '' ){
        $_GET[$get[0]] = isset($get[1]) ? urldecode($get[1]) : '';
      }
    }
  }

$routes       = array_values( array_filter( explode('/',$first_route[0]), 'strlen' ) );

  // Dil öneki: varsayılan dil (tr) önek almaz, diğerleri /en/... şeklinde gelir
  $lang = default_language();
  if( isset($routes[0]) && in_array($routes[0], available_languages(), true) ){
    $lang = array_shift($routes);
  }

$route        = $routes;
$routeType    = 0;

  // Controller'lar yalnizca ihtiyaç duydukları verileri doldurur. Twig'e
  // gönderilen ortak değişkenleri burada güvenli varsayılanlarla başlat.
  $templateDir   = $templateDir ?? null;
  $description   = $description ?? ($settings['site_description'] ?? '');
  $keywords      = $keywords ?? ($settings['site_keywords'] ?? '');
  $languagesL    = $languagesL ?? array();
  $errorText     = $errorText ?? '';
  $successText   = $successText ?? '';
  $product       = $product ?? null;
  $page          = $page ?? null;
  $category      = $category ?? null;
  $fileList      = $fileList ?? array();
  $productList   = $productList ?? array();
  $faqList       = $faqList ?? array();
  $teamList      = $teamList ?? array();
  $commentList   = $commentList ?? array();
  $referenceList = $referenceList ?? array();
  $sliderList    = $sliderList ?? array();
  $query          = $query ?? '';
  $searchPages    = $searchPages ?? array();
  $searchFiles    = $searchFiles ?? array();
  $corporatePages = $corporatePages ?? array();
  $activeSlug     = $activeSlug ?? '';
  $sectorList     = $sectorList ?? array();
  $otherSectors   = $otherSectors ?? array();
  $newsList       = $newsList ?? array();
  $otherNews      = $otherNews ?? array();
  $form_name      = $form_name ?? '';
  $form_email     = $form_email ?? '';
  $form_telephone = $form_telephone ?? '';
  $form_subject   = $form_subject ?? '';
  $form_message   = $form_message ?? '';

  if( !isset($route[0]) ){
    $route[0] = "home";
  }

  // JSON API — Twig/render akışının tamamen dışında, kendi router'ına düşer
  if( $route[0] === 'api' ){
    require __DIR__.'/app/api/router.php';
    exit;
  }

  if( !file_exists( controller($route[0]) ) ){
    $route[0] = "404";
  }

  $title  = $settings["site_title"];

  require controller($route[0]);

  if( $settings["recaptcha"] == 1 ){
    $captcha = false;
  }elseif( $settings["recaptcha"] == 2 ){
    $captcha = true;
  }


  // WhatsApp: numara ayarlardan gelir, yalniz rakamlar birakilir
  $whatsapp_number = isset($settings["whatsapp"]) ? preg_replace('/\D+/', '', $settings["whatsapp"]) : '';
  $whatsapp_link   = $whatsapp_number
    ? 'https://wa.me/'.$whatsapp_number.'?text='.rawurlencode(
        $settings["site_name"].' — merhaba, bilgi almak istiyorum.')
    : '';

  // Admin panelindeki eski/kırık logo yolları header'ı görünmez bırakmasın.
  $siteLogo = !empty($settings['site_logo']) && file_exists(PATH.'/'.$settings['site_logo'])
    ? $settings['site_logo']
    : 'assets/cloud/uploads/af017f46be20ef7d72e6529737837f22.webp';

  // Formlarin geri donus adresi ve durum parametreleri
  $current_url = URL.strtok($_SERVER["REQUEST_URI"], '?');
  $app_query   = array(
    'teklif' => isset($_GET['teklif']) ? $_GET['teklif'] : '',
    'abone'  => isset($_GET['abone'])  ? $_GET['abone']  : ''
  );

 $captchaKey =  $settings["recaptcha_key"];
  $active_menu = route(0); 
  $active_menu2 = route(1); 

  if( route(0) != "admin" ){
    if( !$templateDir ){
      $templateDir = route($routeType);
    }
 
    $contentGet = $templateDir;

    $content  = $conn->prepare("SELECT * FROM pages WHERE page_get=:get ");
    $content->execute(array("get"=>$contentGet));
    $contentRow = $content->fetch(PDO::FETCH_ASSOC);
    localize_row($contentRow, array('page_content' => 'page_content_en'));
    $content = $contentRow['page_content'] ?? '';

    $serviceList = $conn->prepare("SELECT * FROM pages WHERE page_type=:type ");
    $serviceList->execute(array("type"=>1));
    $serviceList = $serviceList->fetchAll(PDO::FETCH_ASSOC);
    localize_pages($serviceList);

    $pageList = $conn->prepare("SELECT * FROM pages WHERE page_type=:type");
    $pageList->execute(array("type"=>2));
    $pageList = $pageList->fetchAll(PDO::FETCH_ASSOC);
    localize_pages($pageList);

    $categoryList = $conn->prepare("SELECT * FROM categories ");
    $categoryList->execute(array());
    $categoryList = $categoryList->fetchAll(PDO::FETCH_ASSOC);
    localize_categories($categoryList);

    $blogList = $conn->prepare("SELECT * FROM pages WHERE page_type=:type ");
    $blogList->execute(array("type"=>3));
    $blogList = $blogList->fetchAll(PDO::FETCH_ASSOC);
    localize_pages($blogList);

    // Menude bos sayfalari gizlemek icin: icerik girilince otomatik gorunur
    $galleryCount = $conn->prepare("SELECT COUNT(*) FROM files WHERE file_type=2");
    $galleryCount->execute();
    $galleryCount = (int)$galleryCount->fetchColumn();

    $catalogCount = $conn->prepare("SELECT COUNT(*) FROM files WHERE file_type=1");
    $catalogCount->execute();
    $catalogCount = (int)$catalogCount->fetchColumn();
    
    echo $twig->render( $templateDir.'.twig',
      array(
                      'site'=>[
                      'url'=>URL,'asset_version'=>ASSET_VERSION,'favicon'=>$settings['favicon'],"logo"=>$siteLogo,"site_name"=>$settings["site_name"],
                      'service_speed'=>$settings["service_speed"],"keywords"=>$settings["site_keywords"],
                      "description"=>$description ?: $settings["site_description"],'languages'=>$languagesL,
                      'indexable'=>INDEXABLE,
                      'schema'=>[
                        '@context'=>'https://schema.org',
                        '@type'=>'LocalBusiness',
                        'name'=>$settings['site_name'],
                        'url'=>URL,
                        'logo'=>URL.'/assets_new/images/logo.png',
                        'telephone'=>$settings['site_telephone'],
                        'email'=>$settings['site_mail'],
                        'address'=>[
                          '@type'=>'PostalAddress',
                          'streetAddress'=>$settings['site_address'],
                          'addressLocality'=>'Bursa',
                          'addressCountry'=>'TR'
                        ]
                      ]
                    ],
    'teamList'=>$teamList , 'description'=>$description , 'keywords'=>$keywords, 'active_menu'=>$active_menu,  'active_menu2'=>$active_menu2,  'captcha'=>$captcha, 'captchaKey'=>$captchaKey, 'errorText'=>$errorText,'successText'=>$successText, 'product'=>$product,'fileList'=>$fileList, 'title'=>$title, 'keywords'=>$keywords, 'description'=>$description , 'sliderList'=>$sliderList,'serviceList'=>$serviceList,'commentList'=>$commentList,'blogList'=>$blogList,'referenceList'=>$referenceList,'settings'=>$settings,'categoryList'=>$categoryList,'pageList'=>$pageList,'page'=>$page,'category'=>$category,'productList'=>$productList,'currency'=>currency($settings["site_currency"]),'faqList'=>$faqList, 'query'=>$query, 'searchPages'=>$searchPages, 'searchFiles'=>$searchFiles, 'corporatePages'=>$corporatePages, 'activeSlug'=>$activeSlug, 'galleryCount'=>$galleryCount, 'catalogCount'=>$catalogCount, 'sectorList'=>$sectorList, 'otherSectors'=>$otherSectors, 'newsList'=>$newsList, 'otherNews'=>$otherNews, 'form_name'=>$form_name, 'form_email'=>$form_email, 'form_telephone'=>$form_telephone, 'form_subject'=>$form_subject, 'form_message'=>$form_message, 'current_url'=>$current_url, 'app_query'=>$app_query, 'whatsapp_number'=>$whatsapp_number, 'whatsapp_link'=>$whatsapp_link, 'legalPage'=>isset($legalPage) ? $legalPage : null
      )
    );
  }
