<?
  global $user;
  if (isset($user)) $cookie = explode(":", base64_decode($user));
  if (isset($cookie[9])) include "themes/$cookie[9]/theme.class";
  else include "themes/default/theme.class";
  $theme = new Theme();
?>