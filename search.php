<?php

include_once "includes/common.inc";

page_header();

if (user_access($user, "search content")) {
  // verify input:
  $type = check_input($type);
  $keys = check_input($keys);

  // build options list:
  foreach (module_list() as $name) {
    if (module_hook($name, "search")) {
      $options .= "<option value=\"$name\"". ($name == $type ? " selected" : "") .">$name</option>\n";
    }
  }

  // build form:
  $form .= "<form action=\"search.php\" method=\"POST\">\n";
  $form .= " <input size=\"50\" value=\"". check_form($keys) ."\" name=\"keys\" TYPE=\"text\">\n";
  $form .= " <select name=\"type\">$options</select>\n";
  $form .= " <input type=\"submit\" value=\"". t("Search") ."\">\n";
  $form .= "</form>\n";

  // visualize form:
  $theme->header();

  if ($form) {
    $theme->box(t("Search"), $form);
  }

  if ($keys) {
    $theme->box(t("Result"), search_data($keys, $type));
  }

  $theme->footer();
}
else {
  $theme->header();
  $theme->box("Access denied", message_access());
  $theme->footer();
}

page_footer();

?>