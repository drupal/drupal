<?

function locale_init() {
  global $languages, $user;
  return ($user->id && $user->language) ? $user->language : key($languages);
}

function t($string) {
  global $languages;
  return ($languages ? locale($string) : $string);
}

?>