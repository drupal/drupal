<?php

function locale_init() {
  global $languages, $user;
  return ($languages ? (($user->id && $user->language) ? $user->language : key($languages)) : 0);
}

function t($string) {
  global $languages;
  return ($languages && function_exists("locale") ? locale($string) : $string);
}

?>