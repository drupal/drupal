<?php
 $handle = opendir('themes');
 while ($file = readdir($handle)) {
   if(!ereg("^\.",$file) && file_exists("themes/$file/theme.class")) $themelist[] = $file;
 }
 closedir($handle);
?>