<?php

include_once "includes/common.inc";

$errors = array(500 => "500 error - internal server error", 
                404 => "404 error - `$REDIRECT_URL' not found", 
                403 => "403 error - access denied - forbidden",
                401 => "401 error - authorization required", 
                400 => "400 error - bad request");

watchdog("error", $errors[$REDIRECT_STATUS]);

header("Location: index.php");

?>