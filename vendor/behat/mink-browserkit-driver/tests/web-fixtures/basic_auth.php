<?php
$SERVER = $app['request']->server->all();

$username = isset($SERVER['PHP_AUTH_USER']) ? $SERVER['PHP_AUTH_USER'] : false;
$password = isset($SERVER['PHP_AUTH_PW']) ? $SERVER['PHP_AUTH_PW'] : false;

if ($username == 'mink-user' && $password == 'mink-password') {
    echo 'is authenticated';
} else {
    $resp = new \Symfony\Component\HttpFoundation\Response();
    $resp->setStatusCode(401);
    $resp->headers->set('WWW-Authenticate', 'Basic realm="Mink Testing Area"');

    echo 'is not authenticated';
}
