<?

// validate user permission:
if (!$user->id || ($user->permissions != 1 && $user->id > 1)) exit();

include_once "includes/theme.inc";
include_once "includes/cron.inc";

function admin_page($mod) {  
  global $repository, $menu, $modules;

  function module($name, $module) {
    global $menu, $modules;
    if ($module["admin"]) $output .= "<A HREF=\"admin.php?mod=$name\">$name</A> | ";
    $menu .= $output;
  }

 ?>
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
  <HTML>
   <HEAD><TITLE><? echo $site_name; ?> administration center</TITLE></HEAD>
   <STYLE>
    body { font-family: helvetica, arial; }
    h1   { font-size: 14pt; font-weight: bold; color: #990000; }
    h2   { font-family: helvetica, arial; font-size: 12pt; font-weight: bold; }
    h3   { font-family: helvetica, arial; font-size: 14pt; font-weight: bold; }
    th	 { font-family: helvetica, arial; text-align: center; background-color: #CCCCCC; color: #995555; }
    td	 { font-family: helvetica, arial; }
   </STYLE>
   <BODY BGCOLOR="#FFFFFF" LINK="#005599" VLINK="#004499" ALINK="#FF0000">
    <H1>Administration center</H1>
 <?
  
  module_iterate("module");
 
 ?> 
    <HR><? echo $menu; ?><A HREF="">home</A><HR>
 <?

  module_execute($mod, "admin");

 ?>
   </BODY>
  </HTML>
 <?
}

admin_page($mod);

?>
