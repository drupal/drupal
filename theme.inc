<?

global $user;
### Verify theme:
if (isset($user->theme) && file_exists("themes/$user->theme/theme.class")) {
  include "themes/$user->theme/theme.class";	
}
else {
  include "config.inc";
  include "themes/$cfg_theme/theme.class";
}

$theme = new Theme();
?>