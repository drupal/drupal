<?php

include_once "includes/common.inc";

function submit_type($name, $module) {
  global $modules;
  if ($module[user]) $modules = array_merge(($modules ? $modules : array()), array($name => $name));
}

$theme->header();

if ($user->id) {
  if ($mod) {
    module_execute($mod, "user");
  }
  else {
    module_iterate("submit_type");

    $output .= "<P>". t("If you have written something or if you have some news or thoughts that you would like to share, then this is the place where you can submit new content.  Fill out this form and your contribution will automatically get whisked away to our submission queue where our moderators will frown at it, poke at it and hopefully post it.") ."</P>";

    $output .= "<FORM ACTION=\"submit.php\" METHOD=\"get\">\n";
    $output .= "<B>". t("Submission type") .":</B><BR>\n";
    foreach ($modules as $key => $value) $options .= "<OPTION VALUE=\"$key\">$value</OPTION>";
    $output .= "<SELECT NAME=\"mod\">$options</SELECT><P>\n";
    $output .= "<INPUT NAME=\"op\" TYPE=\"submit\" VALUE=\"". t("Next step") ."\">\n";

    //» reset «

    $theme->box("Submit", $output);
  }
}
else {
  $theme->box("Submit", t("This page requires a valid user account.  Please <A HREF=\"account.php\">login</A> prior to accessing it."));
}
$theme->footer();

?>
