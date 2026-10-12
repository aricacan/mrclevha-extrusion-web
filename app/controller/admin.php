<?php
  $isAdminAuthenticated = !empty($user['access']['admin_access'])
    && !empty($_SESSION['softyr_adminlogin'])
    && ($user['client_type'] ?? null) == 2;

  if( $isAdminAuthenticated ):
    if( !route(1) ){
      $route[1] = "index";
    }

    if( !file_exists( admin_controller(route(1)) ) ){
      $route[1] = "index";
    }

    require admin_controller( route(1) );
  else:
      $route[1] = "login";
      require admin_controller( route(1) );
  endif;
