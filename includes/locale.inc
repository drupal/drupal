<?

function locale_init() {
  global $languages, $user;
  return ($user->id && $user->language) ? $user->language : key($languages);
}

function t($string) {
  return locale($string);
}

?>