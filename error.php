<H1>Oops, an error occured!</H1>
<PRE>

<B>Temporary debug output:</B><BR>
  * STATUS...: <? echo $REDIRECT_STATUS; ?><BR>
  * URL......: <? echo $REDIRECT_URL; ?><BR>
  * METHOD...: <? echo $REQUEST_METHOD; ?><BR>

<?
 switch($REDIRECT_STATUS) {
   case 500:
    $message = "500 error - internal server error";
    break;
   case 404:
    $message = "404 error - document not found";
    break;
   case 403:
    $message = "403 error - access denied - forbidden";
    break;
   case 401:
    $message = "401 error - authorization required";
    break;
   case 401:
    $message = "400 error - bad request";
    break;
   default:
    $message = "unknown error";
 }

 include "includes/config.inc";
 include "includes/database.inc"; 
 include "includes/watchdog.inc";

 watchdog(3, "message: `$message' - requested url: $REDIRECT_URL - referring url: $HTTP_REFERER");
?>

<B>Processed output:</B><BR>
  * <? echo $message; ?><BR>
  * Return to the <A HREF="">main page</A>.
</PRE>
