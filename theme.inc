<?

global $user;
### Verify theme:
if (isset($user->theme) && file_exists("themes/$user->theme/theme.class.php")) {
  include "themes/$user->theme/theme.class.php";	
}
else {
  include "config.inc";
  include "themes/$cfg_theme/theme.class.php";
}

$theme = new Theme();
?>