<?php
// $Id$

/**
 * Basic theme
 *
 * @package theme system
 */


function theme_help($section) {

  $ouptout = "";

  switch ($section) {
    case 'admin/system/themes#description':
      $output = t("The base theme");
      break;
  }

  return $output;
}

class BaseTheme {
}

function theme_header($title = "") {
  global $base_url;

  $output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
  $output .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">";
  $output .= "<head><title>". $title ? $title : variable_get(site_name, "drupal") ."</title>";
  $output .= theme_head($main);
  $output .= "</head><body style=\"background-color: #fff; color: #000;\"". theme_onload_attribute(). "\">";
  $output .= "<table border=\"0\" cellspacing=\"4\" cellpadding=\"4\"><tr><td style=\"vertical-align: top; width: 170px;\">";

  print $output;
  theme("box", t("Navigation"), @implode("<br />", link_page()));
  theme_blocks("all", $this);
  print "</td><td style=\"vertical-align: top;\">";
}

function theme_links($links, $delimiter = " | ") {
  return implode($delimiter, $links);
}

function theme_image($name) {
  return "misc/$name";
}

function theme_breadcrumb($breadcrumb) {
  print "<div class=\"breadcrumb\">". implode($breadcrumb, " &raquo; ") ."</div>";
}

function theme_node($node, $main) {
  if (module_exist("taxonomy")) {
    $terms = taxonomy_link("taxonomy terms", $node);
  }

  $output = "<h2>$node->title</h2> by ". format_name($node);

  if (count($terms)) {
    $output .= " <small>(". theme("links", $terms) .")</small><br />";
  }

  if ($main && $node->teaser) {
    $output .= $node->teaser;
  }
  else {
    $output .= $node->body;
  }

  if ($links = link_node($node, $main)) {
    $output .= "<br />[ ". theme("links", $links) ." ]";
  }
  $output .= "<hr />";

  print $output;
}

function theme_box($subject, $content, $region = "main") {
  $output = "<h2>$subject</h2><p>$content</p>";
  print $output;
}

/**
 * Render a block.
 *
 * You can style your blocks by defining .block (all blocks),
 * .block-<i>module</i> (all blocks of module <i>module</i>),
 * and \#block-<i>module</i>-<i>delta</i> (specific block of
 * module <i>module</i> with delta <i>delta</i>) in your
 * theme's CSS.
 *
 * @param   $block  object "indexed with" fields from database
 *   table 'blocks' ($block->module, $block->delta, $block->region,
 *   ...) and fields returned by <i>module</i>_block("view")
 *   ($block->subject, $block->content, ...).
 */
function theme_block($block) {
  $output  = "<div class=\"block block-$block->module\" id=\"block-$block->module-$block->delta\">";
  $output .= " <h3>$block->subject</h3>";
  $output .= " <div class=\"content\">$block->content</div>";
  $output .= "</div>";
  print $output;
}

function theme_footer() {
  $output = "</td></tr></table>";
  $output .= theme_closure();
  $output .= "</body></html>";
  print $output;
}

/**
 * Return a marker.  Used to indicate new comments or required form
 * fields.
 */
function theme_mark() {
  return "<span class=\"marker\">*</span>";
}

/**
 * Return a formatted array of items.
 */
function theme_item_list($items = array(), $title = NULL) {
  $output .= "<div class=\"item-list\">";
  if (isset($title)) {
    $output .= "<h3>$title</h3>";
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

/**
 * Return an error message.
 */
function theme_error($message) {
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

/**
 * Execute hook _footer() which is run at the end of the page right
 * before the </body> tag
 */
function theme_closure($main = 0) {
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

  $instance->theme = $name;

  return $instance;
}

/**
 * Render blocks available for (global) $user and $region calling $theme->block($block).
 *
 * @param   $region   main|left|right
 */
function theme_blocks($region) {
  global $user;

  $result = db_query("SELECT * FROM {blocks} WHERE (status = '1' OR custom = '1') ". ($region != "all" ? "AND region = %d " : "") ."ORDER BY weight, module", $region == "left" ? 0 : 1);

  while ($result && ($block = db_fetch_array($result))) {
    if ((($block['status'] && (!$user->uid || !$block['custom'])) || ($block['custom'] && $user->block[$block['module']][$block['delta']])) && (!$block['path'] || preg_match($block['path'], str_replace("?q=", "", request_uri())))) {
      $block = array_merge($block, module_invoke($block['module'], 'block', 'view', $block['delta']));
      if ($block['content']) {
        theme('block', (object) $block);
      }
    }
  }
}

function theme() {
  global $theme;

  $args = func_get_args();

  $function = array_shift($args);

  $name = $theme->theme;

  if (function_exists($name ."_". $function)) {
    return call_user_func_array($name ."_". $function, $args);
  }
  else if (method_exists($theme, $function)) {
    return call_user_method_array($function, $theme, $args);
      // temporary fall-back; can be removed as soon the $theme-object is no more
  }
  else if (function_exists("theme_". $function)) {
    return call_user_func_array("theme_". $function, $args);
  }
  else {
    return call_user_func_array($function, $args);
      // temporary fall-back; can be removed as soon the $theme-object is no more
  }
}

/**
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
