<?php
$username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : false;
$password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : false;

if ($username == 'mink-user' && $password == 'mink-password') {
    echo 'is authenticated';
} else {
    header('WWW-Authenticate: Basic realm="Mink Testing Area"');
    header('HTTP/1.0 401 Unauthorized');

    echo 'is not authenticated';
}
