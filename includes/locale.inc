<?php
// $Id: locale.inc,v 1.13 2001/11/01 11:00:47 dries Exp $

function locale_init() {
  global $languages, $user;
  return ($languages ? (($user->uid && $user->language) ? $user->language : key($languages)) : 0);
}

function t($string) {
  global $languages;
  return ($languages && function_exists("locale") ? locale($string) : $string);
}

?>