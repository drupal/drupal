<?php

include_once "includes/common.inc";

function status($message) {
  if ($message) return "<B>Status:</B> $message<HR>\n";
}

function admin_page($mod) {
  global $user;

 ?>
  <html>
   <head>
    <title><?php echo variable_get(site_name, "drupal"); ?> administration pages</title>
   </head>
   <style>
    body { font-family: helvetica, arial; }
    h1   { font-famile: helvetica, arial; font-size: 18pt; font-weight: bold; color: #660000; }
    h2   { font-family: helvetica, arial; font-size: 18pt; font-weight: bold; color: #000066; }
    h3   { font-family: helvetica, arial; font-size: 14pt; font-weight: bold; color: #006600; }
    th   { font-family: helvetica, arial; text-align: center; vertical-align: top; background-color: #CCCCCC; color: #995555; }
    td   { font-family: helvetica, arial; }
   </style>
   <body bgcolor="#FFFFFF" link="#005599" vlink="#004499" alink="#FF0000">
    <h1>Administration</h1>
    <?php

      $links[] = "<a href=\index.php\">home</a>";
      foreach (module_list() as $name) {
        if (module_hook($name, "link")) $links = array_merge($links, module_invoke($name, "link", "admin"));
      }

      print implode(" | ", $links) ."<hr />";

      if ($mod) module_invoke($mod, "admin");
    ?>
  </body>
 </html>
 <?php
}

if (user_access("access administration pages")) {
  user_rehash();
  admin_page($mod);
}

?>