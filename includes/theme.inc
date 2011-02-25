<?php

/**
* Basic theme
*
* @package theme system
*/
class BaseTheme {
  var $background = "#ffffff";
  var $foreground = "#000000";

  function system($field) {
    $system["name"] = "Basic theme";
    $system["author"] = "Drupal";
    $system["description"] = "Basic theme. Lynx friendly";

    return $system[$field];
  }

  function header($title = "") {
    $output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
    $output .= "<html><head><title>". variable_get(site_name, "drupal") ."</title>";
    $output .= theme_head($main);
    $output .= "</head><body bgcolor=\"$this->background\" text=\"$this->foreground\">";
    $output .= "<table border=\"0\" cellspacing=\"4\" cellpadding=\"4\"><tr><td valign=\"top\" width=\"170\">";

    print $output;
    $this->box(t("Navigation"), @implode("<br />", link_page())); theme_blocks("all", $this);
    print "</td><td valign=\"top\">";

  }

  function links($links, $delimiter = " | ") {
    return implode($delimiter, $links);
  }

  function image($name) {
    return "misc/$name";
  }

  function node($node, $main) {
    if (function_exists("taxonomy_node_get_terms")) {
      foreach (taxonomy_node_get_terms($node->nid) as $term) {
        $terms[] = l($term->name, array("or" => $term->tid), "index");
      }
    }

    $output = "<b>". check_output($node->title) ."</b> by ". format_name($node) ."<br />";
    if (count($terms)) {
      $output .= "<small>(". $this->links($terms) .")</small><br />";
    }
    if ($main && $node->teaser) {
      $output .= check_output($node->teaser, 1);
    }
    else {
      $output .= check_output($node->body, 1);
    }
    if ($links = link_node($node, $main)) {
      $output .= "<br />[ ". $this->links($links) ." ]";
    }
    $output .= "<hr />";

    print $output;
  }

  function box($subject, $content, $region = "main") {
    $output = "<b>". check_output($subject) ."</b><br />". check_output($content) ."<p />";
    print $output;
  }

  function footer() {
    $output = "</td></tr></table>";
    $output .= "</body></html>";
    print $output;
  }

}

function theme_mark() {
  /*
  ** Return a marker.  Used to indicate new comments or required form
  ** fields.
  */
  return "<span style=\"color: red;\">*</span>";
}

function theme_item_list($items = array(), $title = NULL) {
  /*
  ** Return a formatted array of items.
  */

  if (isset($title)) {
    $output .= "<b>$title</b><br />";
  }

  if (isset($items)) {
    foreach ($items as $item) {
      $output .= "- $item<br />";
    }
  }

  return $output;
}

function theme_error($message) {
  /*
  ** Return an error message.
  */
  return "<div style=\"color: red;\">$message</div>";
}

function theme_list() {
  static $list;

  if (!$list) {
    $list = array();
    $result = db_query("SELECT * FROM system where type = 'theme' AND status = '1' ORDER BY name");
    while ($theme = db_fetch_object($result)) {
      if (file_exists($theme->filename)) {
        $list[$theme->name] = $theme;
      }
    }
  }

  return $list;
}

function theme_head($main = 0) {
  $head = module_invoke_all("head", $main);
  return implode($head, "\n");
}

function theme_init() {
  global $user;

  $themes = theme_list();
  $name = $user->theme ? $user->theme : variable_get("theme_default", 0);
  if (is_object($themes[$name])) {
    include_once($themes[$name]->filename);
    $theme_class = "Theme_$name";
    if (class_exists($theme_class)) {
      $obj =& new $theme_class();
      $obj->path = dirname($themes[$name]->filename);
      return $obj;
    }
  }

  $obj =& new BaseTheme;
  return $obj;
}

function theme_blocks($region, &$theme) {
  global $user, $PHP_SELF;

  $result = db_query("SELECT * FROM blocks WHERE (status = '1' OR custom = '1') ". ($region != "all" ? "AND region = '%s' " : "") ."ORDER BY weight, module", $region == "left" ? 0 : 1);

  while ($result && ($block = db_fetch_object($result))) {
    if ((($block->status && (!$user->uid || !$block->custom)) || ($block->custom && $user->block[$block->module][$block->delta])) && (!$block->path || preg_match("|$block->path|", $PHP_SELF))) {
      $block_data = module_invoke($block->module, "block", "view", $block->delta);
      if ($block_data["content"]) {
        $theme->box($block_data["subject"], $block_data["content"], $region);
      }
    }
  }
}

function theme_invoke() {
  global $theme;
  $args = func_get_args();

  $function = array_shift($args);

  if (method_exists($theme, $function)) {
    return call_user_method_array($function, $theme, $args);
  }
  else {
    return call_user_func_array($function, $args);
  }
}

?>
