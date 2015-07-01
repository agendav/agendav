<?php
$vendor_directory = getenv('COMPOSER_VENDOR_DIR');
if ($vendor_directory === false) {
    $vendor_directory = __DIR__.'/../web/vendor';
}

include $vendor_directory . '/autoload.php';
