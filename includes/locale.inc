<?php

function locale_init() {
  global $languages, $user;
  return ($languages ? ((is_object($user) && $user->id && $user->language) ? $user->language : key($languages)) : 0);
}

function t($string) {
  global $languages;
  return ($languages && function_exists("locale") ? locale($string) : $string);
}

?>