<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <title>Cookies page</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
</head>
<body>
    <?php
    $cookies = $app['request']->cookies->all();
    unset($cookies['MOCKSESSID']);

    if (isset($cookies['srvr_cookie'])) {
        $srvrCookie = $cookies['srvr_cookie'];
        unset($cookies['srvr_cookie']);
        $cookies['_SESS'] = '';
        $cookies['srvr_cookie'] = $srvrCookie;
    }

    foreach ($cookies as $name => $val) {
        $cookies[$name] = (string)$val;
    }
    echo str_replace(array('>'), '', var_export($cookies, true));
    ?>
</body>
</html>
