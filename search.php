<?php

include_once "includes/common.inc";

page_header();

// verify input:
$type = check_input($type);
$keys = check_input($keys);

// build options list:
foreach (module_list() as $name) {
  if (module_hook($name, "search")) {
    $options .= "<OPTION VALUE=\"$name\"". ($name == $type ? " SELECTED" : "") .">$name</OPTION>\n";
  }
}

// build form:
$form .= "<FORM ACTION=\"search.php\" METHOD=\"POST\">\n";
$form .= " <INPUT SIZE=\"50\" VALUE=\"". check_form($keys) ."\" NAME=\"keys\" TYPE=\"text\">\n";
$form .= " <SELECT NAME=\"type\">$options</SELECT>\n";
$form .= " <INPUT TYPE=\"submit\" VALUE=\"". t("Search") ."\">\n";
$form .= "</FORM>\n";

// visualize form:
$theme->header();

if ($form) {
  $theme->box(t("Search"), $form);
}

if ($keys) {
  $theme->box(t("Result"), search_data($keys, $type));
}

$theme->footer();

page_footer();

?>