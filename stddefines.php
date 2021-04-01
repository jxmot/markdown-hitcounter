<?php
// some of everything, comment out what is NOT being
// used elsewhere.
define('SRVNAME',      ((isset($_SERVER['SERVER_NAME']) === true) ? $_SERVER['SERVER_NAME']     : 'none'));
define('SRVPROTO', ((isset($_SERVER['SERVER_PROTOCOL']) === true) ? $_SERVER['SERVER_PROTOCOL'] : 'none'));
define('REMADDR',      ((isset($_SERVER['REMOTE_ADDR']) === true) ? $_SERVER['REMOTE_ADDR']     : 'none'));
define('QRYSTR' ,     ((isset($_SERVER['QUERY_STRING']) === true) ? $_SERVER['QUERY_STRING']    : 'none'));
define('HTTPREF',     ((isset($_SERVER['HTTP_REFERER']) === true) ? $_SERVER['HTTP_REFERER']    : 'none'));
define('HTTPHOST',       ((isset($_SERVER['HTTP_HOST']) === true) ? $_SERVER['HTTP_HOST']       : 'none'));
define('DOCROOT',    ((isset($_SERVER['DOCUMENT_ROOT']) === true) ? $_SERVER['DOCUMENT_ROOT']   : 'none'));
define('PATHTRANS',((isset($_SERVER['PATH_TRANSLATED']) === true) ? $_SERVER['PATH_TRANSLATED'] : 'none'));
define('PHPSELF',         ((isset($_SERVER['PHP_SELF']) === true) ? $_SERVER['PHP_SELF']        : 'none'));

// used for assembling URLs to resources as needed
define('HTTPTYPE', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://'));
define('THISSRVR', HTTPTYPE . SRVNAME . pathinfo(PHPSELF)['dirname']);
define('TRUESELF', THISSRVR . '/' . pathinfo(PHPSELF)['basename']);

// if debug is enabled then show stuff....
if(defined('_DEBUGDEF') && _DEBUGDEF === true) {
    echo "\n";
    echo "<p><strong>\n";

    echo 'SRVNAME  : '.SRVNAME."<br>\n";
    echo 'SRVPROTO : '.SRVPROTO."<br>\n";
    echo 'REMADDR  : '.REMADDR."<br>\n";
    echo 'QRYSTR   : '.QRYSTR."<br>\n";
    echo 'HTTPREF  : '.HTTPREF."<br>\n";
    echo 'HTTPHOST : '.HTTPHOST."<br>\n";
    echo 'DOCROOT  : '.DOCROOT."<br>\n";
    echo 'PATHTRANS: '.PATHTRANS."<br>\n";
    echo 'PHPSELF  : '.PHPSELF."<br>\n";

    echo "<br>\n";
    echo 'HTTPTYPE  : '.HTTPTYPE."<br>\n";
    echo 'THISSRVR  : '.THISSRVR."<br>\n";
    echo 'TRUESELF  : '.TRUESELF."<br>\n";

    echo "</strong></p>\n";
    echo "\n";
}
?>