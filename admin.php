<?php
// $Id$

include_once "includes/common.inc";

function status($message) {
  if ($message) {
    return "<b>Status:</b> $message<hr />\n";
  }
}

function admin_page($mod) {
  global $user;

 ?>
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
  <html>
   <head>
    <title><?php echo variable_get("site_name", "drupal") . " " . t("administration pages"); ?></title>
   </head>
   <style>
    body { font-family: helvetica, arial; font-size: 12pt; }
    h1   { font-family: helvetica, arial; font-size: 18pt; font-weight: bold; color: #660000; }
    h2   { font-family: helvetica, arial; font-size: 18pt; font-weight: bold; color: #000066; }
    h3   { font-family: helvetica, arial; font-size: 14pt; font-weight: bold; color: #006600; }
    th   { font-family: helvetica, arial; font-size: 12pt; text-align: center; vertical-align: top; background-color: #CCCCCC; color: #995555; }
    td   { font-family: helvetica, arial; font-size: 12pt; }
    #header { float: top; margin: 0; padding: 0; border-bottom: 1px solid #ccc; }
    #tree { float: left; width: 230px; border-right: 1px solid #ccc; z-index: 1; overflow: hidden; }
    #main { margin-left: 240px; padding: 20px 20px 20px 20px; z-index: 2; }
    #menu { padding-bottom: 18px; text-align: right; }
    #help { padding-bottom: 18px; }
   </style>
   <body bgcolor="#FFFFFF" link="#005599" vlink="#004499" alink="#FF0000">
    <?php
      module_invoke_all("link", "admin");

      /*
      ** Header:
      */

      print "<div id=\"header\">";
      print "<a href=\"index.php\"><img align=\"right\" src=\"misc/druplicon-small.gif\" tag=\"Druplicon - Drupal logo\" /></a>";

      if ($path = menu_path()) {
        print "<h2>". la(t("Administration")) ." > $path</h2>";
      }
      else {
        print "<h2>". t("Administration") ."</h2>";
      }

      print "</div>";

      /*
      ** Menu:
      */

      print "<div id=\"tree\">";
      print menu_tree();
      print "</div>";

      /*
      ** Body:
      */

      print "<div id=\"main\">";

      if ($menu = menu_menu()) {
        print "<div id=\"menu\">$menu</div>";
      }

      if ($help = menu_help()) {
        print "<div id=\"help\">$help</div>";
      }

      module_invoke($mod, "admin");
      print "</div>";

      db_query("DELETE FROM menu");
    ?>
  </body>
 </html>
 <?php
}

if (user_access("access administration pages")) {
  page_header();
  admin_page($mod);
  page_footer();
}
else {
  print message_access();
}

?>
