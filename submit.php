<?php

include_once "includes/common.inc";

if (variable_get(dev_timing, 0)) timer_start();

$theme->header();

if ($user->id) {
  if ($mod) {
    module_invoke($mod, "user");
  }
  else {
    $result = db_query("SELECT * FROM category");

    while ($category = db_fetch_object($result)) {
      if (module_hook($category->type, "user")) $options .= "<OPTION VALUE=\"$category->type\">$category->name</OPTION>";
    }

    $form .= form_item(t("Category"), "<SELECT NAME=\"mod\">$options</SELECT>");
    $form .= form_submit(t("Next step"));

    $output .= "<P>". t("If you have written something or if you have some news or thoughts that you would like to share, then this is the place where you can submit new content.  Fill out this form and your contribution will automatically get whisked away to our submission queue where our moderators will frown at it, poke at it and hopefully post it.") ."</P>";
    $output .= form("submit.php", $form, "get");

    $theme->box("Submit", $output);
  }
}
else {
  $theme->box("Submit", t("This page requires a valid user account.  Please <A HREF=\"account.php\">login</A> prior to accessing it."));
}

$theme->footer();

if (variable_get(dev_timing, 0)) timer_print();

?>
