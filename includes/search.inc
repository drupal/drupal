<?php

function search_form($keys) {
  global $REQUEST_URI;
  $output .= "<form action=\"$REQUEST_URI\" method=\"POST\">\n";
  $output .= " <input size=\"50\" value=\"". check_form($keys) ."\" name=\"keys\">";
  $output .= " <input type=\"submit\" value=\"". t("Search") ."\">\n";
  $output .= "</form>\n";
  return $output;
}

function search_data($keys, $type) {
  if ($keys && $type && $result = module_invoke($type, "search", check_query($keys))) {
    foreach ($result as $entry) {
      $output .= "<p>\n";
      $output .= " <b><u><a href=\"$entry[link]\" />$entry[title]</a></u></b><br />";
      $output .= " <small>$entry[link]". ($entry[user] ? " - ". format_name($entry[user], $entry[name]) : "") ."". ($entry[date] ? " - ". format_date($entry[date], "small") : "") ."</small>";
      $output .= "</p>\n";
    }
  }
  else {
    $output .= t("Your search yielded no results.");
  }

  return $output;
}

?>