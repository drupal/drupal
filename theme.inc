<?
  global $user;

  ### Decode user cookie:
  if (isset($user)) $cookie = explode(":", base64_decode($user));

  ### Verify theme:
  if (isset($cookie[9])) {
   if (file_exists("themes/$cookie[9]/theme.class")) {
     include "themes/$cookie[9]/theme.class";
   }
   else include "themes/default/theme.class";
  }
  else include "themes/default/theme.class";

  $theme = new Theme();
?>