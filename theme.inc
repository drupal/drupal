<?

include "config.inc";
include "template.inc";

global $user;

if ($user->theme && file_exists($themes[$user->theme][0])) {
  include $themes[$user->theme][0];	
}
else {
  include $themes[key($themes)][0];
}

$theme = new Theme();

?>