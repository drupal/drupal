<?php
/**
  Theme System - controls the output of Drupal.

  The theme system allows for nearly all output of the Drupal system to be
  customized by user themes.

  @package theme_system

  @defgroup theme_system
  @{
**/

/* $Id: theme.inc,v 1.156 2003/11/10 09:49:05 kjartan Exp $ */

/**
  Returns the theme header.

  @param $title (optional) override the page title.

  @return a string containing the \a header output.
**/
function theme_header($title = "") {
  global $base_url;

  $output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
  $output .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">";
  $output .= "<head>";
  $output .= " <title>". $title ? $title : variable_get(site_name, "drupal") ."</title>";
  $output .= theme_head($main);
  $output .= " <style type=\"text/css\" media=\"all\">";
  $output .= "  @import url(misc/drupal.css);";
  $output .= " </style>";

  $output .= " </head>";
  $output .= " <body style=\"background-color: #fff; color: #000;\"". theme("onload_attribute"). "\">";
  $output .= "<table border=\"0\" cellspacing=\"4\" cellpadding=\"4\"><tr><td style=\"vertical-align: top; width: 170px;\">";

  $output .= theme("box", t("Navigation"), @implode("<br />", link_page()));
  $output .= render_blocks("all");
  $output .= "</td><td style=\"vertical-align: top;\">";

  return $output;
}

/**
  Returns themed set of links.

  @param $links an array of \a links to be themed.
  @param $delimiter (optional) \a delimiter used to separate the links.

  @return a string containing the \a links output.
**/
function theme_links($links, $delimiter = " | ") {
  return implode($delimiter, $links);
}

/**
  Returns themed image.

  @param $name the \a name of the image file.

  @return a string containing the \a image output.
**/
function theme_image($name) {
  return "misc/$name";
}

/**
  Returns a themed breadcrumb menu.

  @param $breadcrumb an array containing the breadcrumb links.

  @return a string containing the \a breadcrumb output.
**/
function theme_breadcrumb($breadcrumb) {
  return "<div class=\"breadcrumb\">". implode($breadcrumb, " &raquo; ") ."</div>";
}

/**
  Returns themed node.

  The passed $node object provides a all relevant information for displaying a node:
  \li \c $node->nid
  \li \c $node->type i.e. story, blog, forum.
  \li \c $node->title
  \li \c $node->created a unix timestamp.
  \li \c $node->teaser
  \li \c $node->body
  \li \c $node->changed a unix timestamp.
  \li \c $node->uid the id of the poster.
  \li \c $node->username the username of the poster.

  @param $node the \a node to be themed.
  @param $main

  @return a string containing the \a node output.
**/
function theme_node($node, $main) {
  if (module_exist("taxonomy")) {
    $terms = taxonomy_link("taxonomy terms", $node);
  }

  $output = "<h2>$node->title</h2> by ". format_name($node);

  if (count($terms)) {
    $output .= " <small>(". print theme("links", $terms) .")</small><br />";
  }

  if ($main && $node->teaser) {
    $output .= $node->teaser;
  }
  else {
    $output .= $node->body;
  }

  if ($links = link_node($node, $main)) {
    $output .= "<br />[ ". print theme("links", $links) ." ]";
  }
  $output .= "<hr />";

  return $output;
}

/**
  Returns themed box.

  @param $subject the \a subject of the box.
  @param $content the \a content of the box.
  @param $region the \a region of the box.

  @return a string containing the \a box output.
**/
function theme_box($subject, $content, $region = "main") {
  $output = "<h2>$subject</h2><p>$content</p>";
  return $output;
}

/**
  Returns a themed block.

  You can style your blocks by defining .block (all blocks),
  .block-<i>module</i> (all blocks of module <i>module</i>),
  and \#block-<i>module</i>-<i>delta</i> (specific block of
  module <i>module</i> with delta <i>delta</i>) in your
  theme's CSS.

  @param $block object "indexed with" fields from database table 'blocks' ($block->module, $block->delta, $block->region, ...) and fields returned by <i>module</i>_block("view") ($block->subject, $block->content, ...).

  @return a string containing the \a box output.
**/
function theme_block($block) {
  $output  = "<div class=\"block block-$block->module\" id=\"block-$block->module-$block->delta\">";
  $output .= " <h3>$block->subject</h3>";
  $output .= " <div class=\"content\">$block->content</div>";
  $output .= "</div>";
  return $output;
}

/**
  Returns themed page footer.

  @return a string containing the \a footer output.
**/
function theme_footer() {
  $output = "</td></tr></table>";
  $output .= theme_closure();
  $output .= "</body></html>";
  return $output;
}

/**
  Returns themed marker, useful for marking new comments or required form elements.

  @return a string containing the \a mark output.
**/
function theme_mark() {
  return "<span class=\"marker\">*</span>";
}

/**
  Returns themed list of items.

  @param $items (optional) an array of the items to be displayed in a list.
  @param $title (optional) the title of the list.

  @return a string containing the \a list output.
**/
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
  Returns themed error message.

  @param $message the error message to be themed.

  @return a string containing the \a error output.
**/
function theme_error($message) {
  return "<div class=\"error\">$message</div>";
}

/**
  Execute hook _head which is run at the start of the page, and output should be in the head tags.

  @param $main (optional)

  @return a string containing the \a error output.
**/
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
  Execute hook _footer() which is run at the end of the page right
  before the </body> tag.

  @param $main (optional)

  @return a string containing the \a closure output.
**/
function theme_closure($main = 0) {
  $footer = module_invoke_all("footer", $main);
  return implode($footer, "\n");
}

/**
  Call _onload hook in all modules to enable modules to insert javascript
  that will get run once the page has been loaded by the browser.

  @param $theme_onloads (optional) addition onload directives.

  @return a string containing the \a onload output.
**/
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
  return "";
}

/**
  Render blocks available for (global) $user and $region calling $theme->block($block).

  @param   $region   main|left|right

  @return a string containing the \a blocks output.
 **/
function render_blocks($region) {
  global $user;

  $result = db_query("SELECT * FROM {blocks} WHERE (status = '1' OR custom = '1') ". ($region != "all" ? "AND region = %d " : "") ."ORDER BY weight, module", $region == "left" ? 0 : 1);

  $output = "";
  while ($result && ($block = db_fetch_array($result))) {
    if ((($block['status'] && (!$user->uid || !$block['custom'])) || ($block['custom'] && $user->block[$block['module']][$block['delta']])) && (!$block['path'] || preg_match($block['path'], str_replace("?q=", "", request_uri())))) {
      $block = array_merge($block, module_invoke($block['module'], 'block', 'view', $block['delta']));
      if ($block['content']) {
        $output .= theme('block', (object)$block);
      }
    }
  }
  return $output;
}

/**
  Hook Help - returns theme specific help and information.

  @param section defines the \a section of the help to be returned.

  @return a string containing the help output.
**/
function theme_help($section) {
  $ouptout = "";

  switch ($section) {
    case 'admin/system/themes#description':
      $output = t("The base theme");
      break;
  }
  return $output;
}

/**
  Provides a list of currently available themes.

  @param $refresh

  @return an array of the currently available themes.
**/
function list_themes($refresh = 0) {
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

/**
  Initialized the theme system.

  @return the name of the currently selected theme.
**/
function init_theme() {
  global $user;

  $themes = list_themes();
  $name = $user->theme ? $user->theme : variable_get("theme_default", 0);

  $theme->path = "";
  $theme->name = "";

  if (is_object($themes[$name])) {
    include_once($themes[$name]->filename);
    $theme->path = dirname($themes[$name]->filename);
    $theme->name = $name;
  }

  return $theme;
}

/**
  Returns the path to the currently selected theme.

  @return the path to the the currently selected theme.
**/
function path_to_theme() {
  global $theme;
  return $theme->path;
}

/**
  External interface of the theme system to all other modules, and core files.

  All requests for themed functions must go through this function. It examines
  the request and routes it to the appropriate theme function. If the current
  theme does not implement the requested function, then the base theme function
  is called.
  Example: \verbatim $header_text = theme("header"); \endverbatim

  @return the path to the the currently selected theme.
**/
function theme() {
  global $theme;

  $args = func_get_args();
  $function = array_shift($args);

  if (($theme->name != "") && (function_exists($theme->name ."_". $function))) {
    return call_user_func_array($theme->name ."_". $function, $args);
  }
  elseif (function_exists("theme_". $function)){
    return call_user_func_array("theme_". $function, $args);
  }
}

/**  @} End of defgroup theme_system **/
?>
