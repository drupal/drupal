<?php

function error_throttle() {
  print "WARNING: submission rate exceeded.  We detected too much data or events from your IP.  Please wait a few minutes and try again.  If you believe this is not justified, please contact us at <A HREF=\"mailto:". variable_get(site_mail, "root@localhost") ."\">". variable_get(site_mail, "root@localhost") ."</A>.";
}

function error_httpd() {
  global $REDIRECT_STATUS, $REDIRECT_URL, $HTTP_REFERER, $HTTP_USER_AGENT;

  switch($REDIRECT_STATUS) {
    case 500:
      $message = "500 error - internal server error";
      break;
    case 404:
      $message = "404 error - `$REDIRECT_URL' not found";
      break;
    case 403:
      $message = "403 error - access denied - forbidden";
      break;
    case 401:
      $message = "401 error - authorization required";
      break;
    case 400:
      $message = "400 error - bad request";
      break;
    default:
      $message = "unknown error";
  }

  watchdog("error", "message: `$message' - requested url: $REDIRECT_URL - referring url: $HTTP_REFERER - user agent: $HTTP_USER_AGENT");

  print "<PRE>\n";
  print "<H1>Oops, an error occured!</H1>\n";
  print "<B>Processed output:</B><BR>\n";
  print "  * $message<BR>\n";
  print "  * Return to the <A HREF=\"index.php\">main page</A>.\n";
  print "</PRE>\n";
}

include_once "includes/common.inc";

switch ($op) {
  case "throttle":
    error_throttle();
    break;
  default:
    error_httpd();
}

?>