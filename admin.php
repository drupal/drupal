<?

// temporary permission solution:
if (!$user->id || $user->id > 4) exit();

include "includes/admin.inc";

// display admin header:
admin_header();

// generate administrator menu:
$handle = opendir("modules");
while ($file = readdir($handle)) {
  if ($filename = substr($file, 0, strpos($file, ".module"))) {
    if ($filename == $mod) {
      $output .= "$filename | ";
    }
    else {
      include_once "modules/$filename.module";
      if ($module["admin"]) $output .= "<A HREF=\"admin.php?mod=$filename\">$filename</A> | ";
    }
  }
}
closedir($handle);
  
print "<HR>$output <A HREF=\"\">home</A><HR>";

// display administrator body:
if ($mod) {
  include "modules/$mod.module";
  if ($function = $module["admin"]) $function();
}

// display admin footer:
admin_footer();

?>