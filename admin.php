<?php

include_once "includes/common.inc";

// validate user access:
if (!user_access($user)) exit();

function status($message) {
  if ($message) return "<B>Status:</B> $message<HR>\n";
}

function admin_page($mod) {
  global $menu, $user;

  function module($name) {
    global $menu, $user;
    if (function_exists($name. "_admin") && user_access($user, $name)) $output .= "<A HREF=\"admin.php?mod=$name\">$name</A> | ";
    $menu .= $output;
  }

 ?>
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
  <HTML>
   <HEAD><TITLE><?php echo variable_get(site_name, "drupal"); ?> administration</TITLE></HEAD>
   <STYLE>
    body { font-family: helvetica, arial; }
    h1   { font-famile: helvetica, arial; font-size: 18pt; font-weight: bold; color: #660000; }
    h2   { font-family: helvetica, arial; font-size: 18pt; font-weight: bold; color: #000066; }
    h3   { font-family: helvetica, arial; font-size: 14pt; font-weight: bold; color: #006600; }
    th   { font-family: helvetica, arial; text-align: center; vertical-align: top; background-color: #CCCCCC; color: #995555; }
    td   { font-family: helvetica, arial; }
   </STYLE>
   <BODY BGCOLOR="#FFFFFF" LINK="#005599" VLINK="#004499" ALINK="#FF0000">
    <H1>Administration</H1>
    <?php module_iterate("module"); ?>
    <HR><?php echo $menu; ?><A HREF="index.php">home</A><HR>
    <?php if (user_access($user, $mod)) module_invoke($mod, "admin"); ?>
  </BODY>
 </HTML>
 <?php
}

user_rehash();
admin_page($mod);

?>