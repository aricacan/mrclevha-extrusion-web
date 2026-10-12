<?php

/** /api/sectors — sektörler (pages tablosu, page_type=1). */

require_once __DIR__.'/pages_resource.php';
pages_resource_handle($conn, 1, 'Sektör', $slug);
