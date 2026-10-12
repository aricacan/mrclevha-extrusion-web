<?php

/** /api/pages — kurumsal sayfalar (pages tablosu, page_type=2). */

require_once __DIR__.'/pages_resource.php';
pages_resource_handle($conn, 2, 'Sayfa', $slug);
