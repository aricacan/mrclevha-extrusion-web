<?php

/**
 * JSON API dispatcher — /api/{kaynak}/{slug}
 *
 * Twig render akışının tamamen dışındadır; index.php bu dosyayı
 * bulur bulmaz require edip exit eder. $conn, $settings, route()
 * app/init.php'den zaten hazır durumda gelir.
 */

require_once __DIR__.'/helpers.php';
require_once __DIR__.'/format.php';

// Public GET endpoints can be consumed cross-origin, but credentials and
// write operations must never be exposed to arbitrary origins.
$allowedOrigin = isset($_ENV['API_ALLOWED_ORIGIN']) && $_ENV['API_ALLOWED_ORIGIN'] !== ''
  ? rtrim($_ENV['API_ALLOWED_ORIGIN'], '/')
  : URL;
if( isset($_SERVER['HTTP_ORIGIN']) && rtrim($_SERVER['HTTP_ORIGIN'], '/') === $allowedOrigin ){
  header('Access-Control-Allow-Origin: '.$allowedOrigin);
  header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');

if( api_method() === 'OPTIONS' ){
  http_response_code(204);
  exit;
}

$resource = route(1);
$slug     = route(2);

switch( $resource ){
  case 'blog':
    require __DIR__.'/blog.php';
    break;
  case 'products':
    require __DIR__.'/products.php';
    break;
  case 'categories':
    require __DIR__.'/categories.php';
    break;
  case 'catalogs':
    require __DIR__.'/catalogs.php';
    break;
  case 'sectors':
    require __DIR__.'/sectors.php';
    break;
  case 'pages':
    require __DIR__.'/pages.php';
    break;
  case 'tickets':
    require __DIR__.'/tickets.php';
    break;
  case 'subscribers':
    require __DIR__.'/subscribers.php';
    break;
  case 'search':
    require __DIR__.'/search.php';
    break;
  case 'stats':
    require __DIR__.'/stats.php';
    break;
  case 'all':
    require __DIR__.'/all.php';
    break;
  case '':
  case false:
    api_send(array(
      'name'      => 'Cappafe API',
      'version'   => '1.2',
      'endpoints' => array(
        'GET/POST /api/blog', 'GET/PUT/DELETE /api/blog/{slug}',
        'GET/POST /api/products', 'GET/PUT/DELETE /api/products/{slug}',
        'GET/POST /api/categories', 'GET/PUT/DELETE /api/categories/{slug}',
        'GET/POST /api/catalogs', 'GET/PUT/DELETE /api/catalogs/{id}',
        'GET/POST /api/sectors', 'GET/PUT/DELETE /api/sectors/{slug}',
        'GET/POST /api/pages', 'GET/PUT/DELETE /api/pages/{slug}',
        'GET/POST/PUT/DELETE /api/tickets (auth required on all methods)',
        'GET/POST/DELETE /api/subscribers (auth required on all methods)',
        'GET /api/search?q=...',
        'GET /api/stats (auth required)',
        'GET /api/all — tüm içerik tek istekte, sayfalamasız (ERP ilk senkronizasyon)'
      )
    ));
    break;
  default:
    api_error('Bilinmeyen kaynak: '.$resource, 404, 'unknown_resource');
}
