<?

include_once "includes/common.inc";

function find_module($name, $module) {
  global $options, $type;
  if ($module["find"]) $options .= "<OPTION VALUE=\"$name\"". ($name == $type ? " SELECTED" : "") .">$name</OPTION>\n";
}

module_iterate("find_module");

$search .= "<FORM ACTION=\"search.php\" METHOD=\"POST\">\n";
$search .= " <INPUT SIZE=\"50\" VALUE=\"". check_textfield($keys) ."\" NAME=\"keys\" TYPE=\"text\">\n";
$search .= " <SELECT NAME=\"type\">$options</SELECT>\n";
$search .= " <INPUT TYPE=\"submit\" VALUE=\"Search\">\n";
$search .= "</FORM>\n";

$output = search_data($keys, $type);

$theme->header();
$theme->box("Search", $search);
$theme->box("Result", $output);
$theme->footer();

?>