<?php

$hasCookie = isset($_COOKIE['foo']);
setcookie('foo', 'bar', 0, '/', null, false, true);

?>
<!DOCTYPE html>
<html>
<head>
    <title>HttpOnly Cookie Test</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
</head>
<body>
	<div id="cookie-status">Has Cookie: <?php echo json_encode($hasCookie) ?></div>
</body>
</html>
