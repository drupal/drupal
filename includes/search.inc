<?php
// $Id$

/*
** Format a single result entry of a search query:
*/

function search_item($item, $type) {
  $output .= " <b><u><a href=\"". $item["link"] ."\">". $item["title"] ."</a></u></b><br />";
  $output .= " <small>$type ". ($item["user"] ? " - ". $item["user"] : "") ."". ($item["date"] ? " - ". format_date($item["date"], "small") : "") ."</small>";
  $output .= "<br /><br />";

  return $output;
}

/*
** Render a generic search form:
*/

function search_form($action = 0, $query = 0, $options = 0) {
  global $keys;

  if (!$action) {
    $action = "module.php?mod=search";
  }

  if (!$query) {
    $query = $keys;
  }

  $output .= " <input type=\"text\" size=\"50\" value=\"". check_form($keys) ."\" name=\"keys\">";
  $output .= " <input type=\"submit\" value=\"". t("Search") ."\">\n";

  if ($options != 0) {
    $output .= "<br />";
    $output .= t("Restrict search to") .": ";

    foreach (module_list() as $name) {
      if (module_hook($name, "search")) {
        $output .= " <input type=\"checkbox\" name=\"edit[type][$name]\" ". ($edit["type"][$name] ? " checked=\"checked\"" : "") ."/> ". t($name);
      }
    }
  }

  return form($output, "post", $action);
}

/*
** Collect the search results:
*/

function search_data() {
  global $keys, $edit;

  $keys = check_input($keys);

  if ($keys) {
    foreach (module_list() as $name) {
      if (module_hook($name, "search") && (!$edit["type"] || $edit["type"][$name]) && ($result = module_invoke($name, "search", check_query($keys)))) {
        foreach ($result as $entry) {
          $output .= search_item($entry, $name);
        }
      }
    }
    if(!$output) {
      $output .= t("Your search yielded no results.");
    }
  }

  return $output;
}

/*
** Display the search form and the resulting data:
*/

function search_type($type = 0, $action = 0, $query = 0, $options = 0) {
  global $edit;

  if ($type) {
    $edit["type"][$type] = "on";
  }

  return search_form($action, $query, $options) . search_data();
}

?>
