<?
#  Future improvements:
#  --------------------
#  1. Automatically report all errors by e-mail.
#  2. Keep a list of all errors either on file or in a MySQL table.
#  3. Auto-redirect visitor to main page within x seconds.
?>

<H1>Oops, an error occured!</H1>
<PRE>

<B>Temporary debug output:</B><BR>
  * STATUS...: <? echo $REDIRECT_STATUS; ?><BR>
  * URL......: <? echo $REDIRECT_URL; ?><BR>
  * METHDOD..: <? echo $REQUEST_METHOD; ?><BR>

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
?>

<B>Processed output:</B><BR>
  * <? echo $message; ?><BR>

</PRE>
