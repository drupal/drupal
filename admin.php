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
    <link rel="stylesheet" type="text/css" media="print" href="misc/print.css" />
    <style type="text/css" title="layout" media="Screen">
      @import url("misc/admin.css");
    </style>
   </head>
   <body>
    <?php
      // NOTE: we include a dummy "print.css" to remove the "flash of unstyled content" (FUOC) problems in IE.

      module_invoke_all("link", "admin");

      /*
      ** Menu:
      */

      print "<div id=\"menu\">";
      echo "<h1><a href=\"index.php\">". variable_get("site_name", "drupal") ."</a></h1>";
      print menu_tree() ;

      print "</div>";

      /*
      ** Body:
      */

      print "<a href=\"http://drupal.org/\"><img align=\"right\" src=\"misc/druplicon-small.gif\" alt=\"Druplicon - Drupal logo\" border=\"0\" /></a>";
      print "<div id=\"main\">";

      if ($path = menu_path()) {
        print "<h2>". la(t("Administration")) ." &gt; $path</h2>";
      }
      else {
        print "<h2>". t("Administration") ."</h2>";
      }

      if ($menu = menu_menu()) {
        print "$menu<br />";
      }

      print "<br /><hr /><br />";

      if ($help = menu_help()) {
        print "<small>$help</small><br /><br />";
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
