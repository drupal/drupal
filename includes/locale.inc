<?php
// $Id: locale.inc,v 1.12 2001/10/20 18:57:07 natrak Exp $

function locale_init() {
  global $languages, $user;
  return ($languages ? (($user->uid && $user->language) ? $user->language : key($languages)) : 0);
}

function t($string) {
  global $languages;
  return ($languages && function_exists("locale") ? locale($string) : $string);
}

?>