<?php

/** /api/search?q=... — site genelinde arama (sayfalar + kataloglar). */

$method = api_method();

if( $method !== 'GET' ){
  api_error('Desteklenmeyen metod: '.$method, 405, 'method_not_allowed');
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if( mb_strlen($query) < 2 ){
  api_error('q parametresi en az 2 karakter olmalıdır.', 422, 'validation_error');
}

$like = '%'.$query.'%';

$pageStmt = $conn->prepare(
  "SELECT page_get, page_type, page_name, page_name_en, page_description, page_description_en
     FROM pages
    WHERE page_name LIKE :q1 OR page_description LIKE :q2 OR page_content LIKE :q3
       OR page_name_en LIKE :q4 OR page_description_en LIKE :q5 OR page_content_en LIKE :q6
    ORDER BY page_type ASC
    LIMIT 30"
);
$pageStmt->execute(array('q1'=>$like,'q2'=>$like,'q3'=>$like,'q4'=>$like,'q5'=>$like,'q6'=>$like));
$pageRows = $pageStmt->fetchAll(PDO::FETCH_ASSOC);

$typeKind = array(1 => 'sector', 2 => 'page', 3 => 'blog');

$pages = array_map(function($row) use ($typeKind){
  return array(
    'kind'          => isset($typeKind[$row['page_type']]) ? $typeKind[$row['page_type']] : 'page',
    'slug'          => $row['page_get'],
    'name'          => $row['page_name'],
    'nameEn'        => $row['page_name_en'],
    'description'   => $row['page_description'],
    'descriptionEn' => $row['page_description_en']
  );
}, $pageRows);

$fileStmt = $conn->prepare("SELECT file_id, file_name, file_url FROM files WHERE file_type=1 AND file_name LIKE :q LIMIT 20");
$fileStmt->execute(array('q' => $like));
$fileRows = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

$catalogs = array_map(function($row){
  return array(
    'kind' => 'catalog',
    'id'   => (int)$row['file_id'],
    'name' => $row['file_name'],
    'url'  => api_absolute_url($row['file_url'])
  );
}, $fileRows);

api_send(array(
  'data' => array(
    'pages'    => $pages,
    'catalogs' => $catalogs
  ),
  'meta' => array('query' => $query, 'total' => count($pages) + count($catalogs))
));
