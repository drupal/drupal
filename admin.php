<?php

include_once "includes/common.inc";

// validate user access:
if (!user_access($user)) exit();

function admin_page($mod) {
  global $repository, $site_name, $menu, $modules, $user;

  function module($name, $module) {
    global $menu, $modules, $user;
    if ($module["admin"]) $output .= (user_access($user, $name) ? "<A HREF=\"admin.php?mod=$name\">$name</A> | " : " $name | ");
    $menu .= $output;
  }

 ?>
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
  <HTML>
   <HEAD><TITLE><?php echo $site_name; ?> administration</TITLE></HEAD>
   <STYLE>
    body { font-family: helvetica, arial; }
    h1   { font-size: 18pt; font-weight: bold; color: #990000; }
    h2   { font-family: helvetica, arial; font-size: 18pt; font-weight: bold; }
    h3   { font-family: helvetica, arial; font-size: 14pt; font-weight: bold; }
    th   { font-family: helvetica, arial; text-align: center; vertical-align: top; background-color: #CCCCCC; color: #995555; }
    td   { font-family: helvetica, arial; }
   </STYLE>
   <BODY BGCOLOR="#FFFFFF" LINK="#005599" VLINK="#004499" ALINK="#FF0000">
    <H1>Administration</H1>
 <?php

  ksort($repository);
  module_iterate("module");

 ?>
    <HR><?php echo $menu; ?><A HREF="">home</A><HR>
 <?php

  if (user_access($user, $mod)) module_execute($mod, "admin");

 ?>
  </BODY>
 </HTML>
 <?php
}

admin_page($mod);

?>