<?php

include_once "includes/common.inc";

page_header();

$theme->header();

if (user_access("post content")) {
  if ($mod) {
    module_invoke($mod, "user");
  }
  else {
    foreach (module_list() as $name) {
      if (module_hook($name, "user")) $options .= "<option value=\"$name\">". t($name) ."</option>";
    }

    $form .= form_item(t("Submission type"), "<SELECT NAME=\"mod\">$options</SELECT>");
    $form .= form_submit(t("Next step"));

    $output .= form($form, "get");

    $theme->box(t("Submit"), $output);
  }
}
else {
  $theme->box(t("Submit"), message_access());
}

$theme->footer();

page_footer();

?>
