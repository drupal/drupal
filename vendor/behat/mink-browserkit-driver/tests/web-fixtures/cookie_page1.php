<?php
    $resp = new Symfony\Component\HttpFoundation\Response();
    $cook = new Symfony\Component\HttpFoundation\Cookie('srvr_cookie', 'srv_var_is_set', 0, '/');
    $resp->headers->setCookie($cook);
?>
<!doctype html public "-//w3c//dtd xhtml 1.1//en" "http://www.w3.org/tr/xhtml11/dtd/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <title>basic form</title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <script>
    </script>
</head>
<body>
    basic page with cookie set from server side
</body>
</html>
