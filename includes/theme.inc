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
    global $base_url;

    $output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
    $output .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">";
    $output .= "<head><title>". ($title ? $title : variable_get('site_name', "drupal")) ."</title>";
    $output .= theme_head($main);
    $output .= "</head><body style=\"background-color: $this->background; color: $this->foreground;\"". theme_onload_attribute(). "\">";
    $output .= "<table border=\"0\" cellspacing=\"4\" cellpadding=\"4\"><tr><td style=\"vertical-align: top; width: 170px;\">";

    print $output;
    $this->box(t("Navigation"), @implode("<br />", link_page())); theme_blocks("all", $this);
    print "</td><td style=\"vertical-align: top;\">";

  }

  function links($links, $delimiter = " | ") {
    return implode($delimiter, $links);
  }

  function image($name) {
    return "misc/$name";
  }

  function breadcrumb($breadcrumb) {
    print "<div class=\"breadcrumb\">". implode($breadcrumb, " &raquo; ") ."</div>";
  }

  function node($node, $main) {
    if (module_exist("taxonomy")) {
      $terms = taxonomy_link("taxonomy terms", $node);
    }

    $output = "<b>$node->title</b> by ". format_name($node) ."<br />";

    if (count($terms)) {
      $output .= "<small>(". $this->links($terms) .")</small><br />";
    }

    if ($main && $node->teaser) {
      $output .= $node->teaser;
    }
    else {
      $output .= $node->body;
    }
    if ($links = link_node($node, $main)) {
      $output .= "<br />[ ". $this->links($links) ." ]";
    }
    $output .= "<hr />";

    print $output;
  }

  function box($subject, $content, $region = "main") {
    $output = "<p><b>$subject</b><br />$content</p>";
    print $output;
  }

  function block($subject, $content, $region = "main") {
    global $theme;

    $theme->box($subject, $content, $region);
  }

  function footer() {
    $output = "</td></tr></table>";
    $output .= theme_footer();
    $output .= "</body></html>";
    print $output;
  }

}  // End of BaseTheme class //

function theme_mark() {
  /*
  ** Return a marker.  Used to indicate new comments or required form
  ** fields.
  */
  return "<span class=\"marker\">*</span>";
}

function theme_item_list($items = array(), $title = NULL) {
  /*
  ** Return a formatted array of items.
  */
  $output .= "<div class=\"item-list\">";
  if (isset($title)) {
    $output .= "<div class=\"title\">$title</div>";
  }

  if (isset($items)) {
    $output .= "<ul>";
    foreach ($items as $item) {
      $output .= "<li>$item</li>";
    }
    $output .= "</ul>";
  }
  $output .= "</div>";
  return $output;
}

function theme_error($message) {
  /*
  ** Return an error message.
  */
  return "<div class=\"error\">$message</div>";
}

function theme_list($refresh = 0) {
  static $list;

  if ($refresh) {
    unset($list);
  }

  if (!$list) {
    $list = array();
    $result = db_query("SELECT * FROM {system} where type = 'theme' AND status = '1' ORDER BY name");
    while ($theme = db_fetch_object($result)) {
      if (file_exists($theme->filename)) {
        $list[$theme->name] = $theme;
      }
    }
  }

  return $list;
}

function theme_head($main = 0) {
  global $base_url;
  $output .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
  $output .= "<base href=\"$base_url/\" />\n";
  $output .= "<style type=\"text/css\">\n";
  $output .= "@import url(misc/drupal.css);\n";
  $output .= "</style>\n";
  $head = module_invoke_all("head", $main);
  $output .= implode($head, "\n");
  return $output;
}

/*
 * Execute hook _footer() which is run at the end of the page right before
 * the </body> tag
 */
function theme_footer($main = 0) {
  $footer = module_invoke_all("footer", $main);
  return implode($footer, "\n");
}

function theme_init() {
  global $user;

  $themes = theme_list();
  $name = $user->theme ? $user->theme : variable_get("theme_default", 0);

  if (is_object($themes[$name])) {
    include_once($themes[$name]->filename);
    $class = "Theme_$name";
    $instance =& new $class();
    $instance->path = dirname($themes[$name]->filename);
  }
  else {
    $instance =& new BaseTheme;
  }

  return $instance;
}

/**
 * Render blocks available for $user and $region calling $theme->block($region).
 *
 * @param   string  $region   main|left|right
 */
function theme_blocks($region) {
  global $user;

  $result = db_query("SELECT * FROM {blocks} WHERE (status = '1' OR custom = '1') ". ($region != "all" ? "AND region = %d " : "") ."ORDER BY weight, module", $region == "left" ? 0 : 1);

  while ($result && ($block = db_fetch_object($result))) {
    if ((($block->status && (!$user->uid || !$block->custom)) || ($block->custom && $user->block[$block->module][$block->delta])) && (!$block->path || preg_match($block->path, str_replace("?q=", "", request_uri())))) {
      $block_data = module_invoke($block->module, "block", "view", $block->delta);
      if ($block_data["content"]) {
        theme("block", $block_data["subject"], $block_data["content"], $region);
      }
    }
  }
}

function theme() {
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

/*
 * Call _onload hook in all modules to enable modules to insert javascript
 * that will get run once the page has been loaded by the browser
 */
function theme_onload_attribute($theme_onloads = array()) {
  if (!is_array($theme_onloads)) {
    $theme_onloads = array($theme_onloads);
  }
  // Merge theme onloads (javascript rollovers, image preloads, etc.)
  // with module onloads (htmlarea, etc.)
  $onloads = array_merge(module_invoke_all("onload"), $theme_onloads);
  if (count($onloads)) {
    return " onload=\"" . implode("; ", $onloads) . "\"";
  }
  return;
}

?>
