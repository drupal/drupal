<?php

include_once "includes/common.inc";

page_header();

$theme->header();

if ($user->id) {
  if ($mod) {
    module_invoke($mod, "user");
  }
  else {
    foreach (module_list() as $name) {
      if (module_hook($name, "user")) $options .= "<OPTION VALUE=\"$name\">$name</OPTION>";
    }

    $form .= form_item(t("Type"), "<SELECT NAME=\"mod\">$options</SELECT>");
    $form .= form_submit(t("Next step"));

    $output .= "<P>". t("If you have written something or if you have some news or thoughts that you would like to share, then this is the place where you can submit new content.  Fill out this form and your contribution will automatically get whisked away to our submission queue where our moderators will frown at it, poke at it and hopefully post it.") ."</P>";
    $output .= form("submit.php", $form, "get");

    $theme->box("Submit", $output);
  }
}
else {
  $theme->box("Submit", message_account());
}

$theme->footer();

page_footer();

?>
