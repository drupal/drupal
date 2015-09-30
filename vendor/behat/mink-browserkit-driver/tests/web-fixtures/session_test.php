<?php
$session = $app['request']->getSession();

if ($app['request']->query->has('login')) {
    $session->migrate();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
    <title>Session Test</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
    <script>
    </script>
</head>
<body>
    <div id="session-id"><?php echo $session->getId() ?></div>
</body>
</html>
