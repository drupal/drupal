<?php
session_name('_SESS');
session_start();

if (isset($_GET['login'])) {
    session_regenerate_id();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
</head>
<body>
    <div id="session-id"><?php echo session_id() ?></div>
</body>
</html>
