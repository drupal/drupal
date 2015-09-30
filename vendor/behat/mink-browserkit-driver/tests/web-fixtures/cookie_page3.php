<?php

$hasCookie = $app['request']->cookies->has('foo');
$resp = new Symfony\Component\HttpFoundation\Response();
$cook = new Symfony\Component\HttpFoundation\Cookie('foo', 'bar');
$resp->headers->setCookie($cook);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <title>HttpOnly Cookie Test</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
    <script>
    </script>
</head>
<body>
    <div id="cookie-status">Has Cookie: <?php echo json_encode($hasCookie) ?></div>
</body>
</html>
