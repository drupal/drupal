<?php
 $handle=opendir('themes');
 while ($file = readdir($handle)) {
   if(!ereg("[.]",$file)) $themelist .= "$file ";
 }
 closedir($handle);
?>