<?php

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
   </style>
   <body bgcolor="#FFFFFF" link="#005599" vlink="#004499" alink="#FF0000">
    <h1><?php echo t("Administration"); ?></h1>
    <?php

      $links[] = "<a href=\"index.php\">" . t("home") . "</a>";
      foreach (module_list() as $name) {
        if (module_hook($name, "link")) {
          $links = array_merge($links, module_invoke($name, "link", "admin"));
        }
      }

      print implode(" | ", $links) ."<hr />";

      if ($mod) {
        module_invoke($mod, "admin");
      }
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
