<?php
/////////////////////// Don Change //////////////////////////////

$site_url = "http://localhost/";
$site_session_name="HasCodingCMS";
date_default_timezone_set('Europe/Istanbul');



//// View Php Error
///
///  developer   -- show error
///  product    -- hide error
///
$error_view = "developer";
















/////////////////////// Don't Change //////////////////////////////

ob_start();
ini_set('session.cookie_domain', $site_url );
session_name($site_session_name);
session_start();


if($error_view=="developer")
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}else
{

    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

