<?

function locale_load() {
  global $languages, $user;
  return ($user->language) ? $user->language : key($languages);
}

function t($string) {
  return locale($string);
}

?>