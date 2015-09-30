<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <title>Basic Form</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
    <script>
    </script>
</head>
<body>
    Previous cookie: <?php
    if ($app['request']->cookies->has('srvr_cookie')) {
        echo $app['request']->cookies->get('srvr_cookie');
    } else {
        echo 'NO';
    }
    ?>
</body>
</html>
