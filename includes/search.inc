<?php

function search_form($keys) {
  global $REQUEST_URI;
  $output .= "<FORM ACTION=\"$REQUEST_URI\" METHOD=\"POST\">\n";
  $output .= " <INPUT SIZE=\"50\" VALUE=\"". check_form($keys) ."\" NAME=\"keys\">";
  $output .= " <INPUT TYPE=\"submit\" VALUE=\"". t("Search") ."\">\n";
  $output .= "</FORM>\n";
  return $output;
}

function search_data($keys, $type) {
  if ($keys && $type && $result = module_invoke($type, "search", check_input($keys))) {
    foreach ($result as $entry) {
      $output .= "<P>\n";
      $output .= " <B><U><A HREF=\"$entry[link]\">$entry[title]</A></U></B><BR>";
      $output .= " <SMALL>$entry[link]". ($entry[user] ? " - ". format_username($entry[user]) : "") ."". ($entry[date] ? " - ". format_date($entry[date], "small") : "") ."</SMALL>";
      $output .= "</P>\n";
    }
  }
  else {
    $output .= t("Your search yielded no results.");
  }

  return $output;
}

?>