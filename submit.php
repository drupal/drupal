<?php

include_once "includes/common.inc";

$theme->header();

if ($user->id) {
  if ($mod) {
    module_execute($mod, "user");
  }
  else {
    $result = db_query("SELECT * FROM category");

    $output .= "<P>". t("If you have written something or if you have some news or thoughts that you would like to share, then this is the place where you can submit new content.  Fill out this form and your contribution will automatically get whisked away to our submission queue where our moderators will frown at it, poke at it and hopefully post it.") ."</P>";

    $output .= "<FORM ACTION=\"submit.php\" METHOD=\"get\">\n";
    $output .= "<B>". t("Category") .":</B><BR>\n";
    while ($category = db_fetch_object($result)) {
      if (module_hook($category->type, "user")) $options .= "<OPTION VALUE=\"$category->type\">$category->name</OPTION>";
    }
    $output .= "<SELECT NAME=\"mod\">$options</SELECT><P>\n";
    $output .= "<INPUT NAME=\"op\" TYPE=\"submit\" VALUE=\"". t("Next step") ."\">\n";

    $theme->box("Submit", $output);
  }
}
else {
  $theme->box("Submit", t("This page requires a valid user account.  Please <A HREF=\"account.php\">login</A> prior to accessing it."));
}
$theme->footer();

?>
