<?php
require_once 'HTTP/Header2.php';

$h = new HTTP_Header2;
$s = 200;

foreach ($_GET as $header => $value) {
    if (!strcasecmp('redirect', $header)) {
        $h->redirect($value);
    }
    if (strcasecmp('status', $header)) {
        $h->setHeader($header, $value);
    } else {
        $s = $value;
    }
}
$h->sendHeaders();
$h->sendStatusCode($s);
?>
