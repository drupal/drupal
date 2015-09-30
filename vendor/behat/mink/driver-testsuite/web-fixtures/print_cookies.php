<!DOCTYPE html>
<html>
<head>
    <title>Cookies page</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
</head>
<body>
    <?php echo str_replace('>', '', var_export($_COOKIE, true)); ?>
</body>
</html>
