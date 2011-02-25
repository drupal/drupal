<?php

/**
 * @file
 *
 * Theme System - controls the output of Drupal.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by user themes.
 *
 * @see <a href="http://drupal.org/node/view/253">Theme system</a>
 * @see themeable
 */

/**
 * Hook Help - returns theme specific help and information.
 *
 * @param section defines the @a section of the help to be returned.
 *
 * @return a string containing the help output.
 */
function theme_help($section) {
  $output = '';

  switch ($section) {
    case 'admin/system/themes#description':
      $output = t("The base theme");
      break;
  }
  return $output;
}

/**
 * Initialize the theme system by loading the theme.
 *
 * @return the name of the currently selected theme.
 */
function init_theme() {
  global $user;

  $themes = list_themes();

  /*
  ** Only select the user selected theme if it is availible in the
  ** list of enabled themes.
  */

  $theme = $themes[$user->theme] ? $user->theme : variable_get("theme_default", 0);

  include_once($themes[$theme]->filename);

  return $theme;
}

/**
 * Provides a list of currently available themes.
 *
 * @param $refresh
 *
 * @return an array of the currently available themes.
 */
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
 * External interface of the theme system to all other modules, and core files.
 *
 * All requests for themed functions must go through this function. It examines
 * the request and routes it to the appropriate theme function. If the current
 * theme does not implement the requested function, then the base theme function
 * is called. Example: @verbatim $header_text = theme("header"); @endverbatim
 *
 * @return themed output.
 */
function theme() {
  global $theme;

  $args = func_get_args();
  $function = array_shift($args);

  if (($theme != '') && (function_exists($theme ."_". $function))) {
    return call_user_func_array($theme ."_". $function, $args);
  }
  elseif (function_exists("theme_". $function)){
    return call_user_func_array("theme_". $function, $args);
  }
}

/**
 * Returns the path to the currently selected theme.
 *
 * @return the path to the the currently selected theme.
 */
function path_to_theme() {
  global $theme;

  $themes = list_themes();

  return dirname($themes[$theme]->filename);
}

/**
 * @defgroup themeable Themeable functions
 * @{
 *
 * Themeable functions - functions that can be styled differently in themes.
 *
 * @see theme
 * @see theme.inc
 */

/**
 * Returns an entire Drupal page displaying the supplied content.
 *
 * @param $content a string containing the content to display
 * @param $title (optional) page title (\<head>\<title>)
 * @param $breadcrumb (optional) page breadcrumb
 *
 * @see drupal_breadcrumb
 *
 * @return a string containing the @a page output.
 */
function theme_page($content, $title = NULL, $breadcrumb = NULL) {
  if (isset($title)) {
    drupal_set_title($title);
  }
  if (isset($breadcrumb)) {
    drupal_set_breadcrumb($breadcrumb);
  }

  $output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
  $output .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">";
  $output .= "<head>";
  $output .= " <title>". (drupal_get_title() ? drupal_get_title() : variable_get('site_name', "drupal")) ."</title>";
  $output .= drupal_get_html_head();

  $output .= " </head>";
  $output .= " <body style=\"background-color: #fff; color: #000;\"". theme("onload_attribute"). "\">";
  $output .= "<table border=\"0\" cellspacing=\"4\" cellpadding=\"4\"><tr><td style=\"vertical-align: top; width: 170px;\">";

  $output .= theme("box", t("Navigation"), @implode("<br />", link_page()));
  $output .= theme("blocks", "all");
  $output .= "</td><td style=\"vertical-align: top;\">";

  $output .= theme("breadcrumb", drupal_get_breadcrumb());
  $output .= "<h1>" . drupal_get_title() . "</h1>";
  if ($help = menu_get_active_help()) {
    $output .= "<small>$help</small><hr />";
  }

  $output .= "\n<!-- begin content -->\n";
  $output .= $content;
  $output .= "\n<!-- end content -->\n";

  $output .= "</td></tr></table>";
  $output .= theme_closure();
  $output .= "</body></html>";

  return $output;
}

/**
 * Returns themed set of links.
 *
 * @param $links an array of @a links to be themed.
 * @param $delimiter (optional) @a delimiter used to separate the links.
 *
 * @return a string containing the @a links output.
 */
function theme_links($links, $delimiter = " | ") {
  return implode($delimiter, $links);
}

/**
 * Returns themed image.
 *
 * @param $name the @a name of the image file.
 *
 * @return a string containing the @a image output.
 */
function theme_image($name) {
  return "misc/$name";
}

/**
 * Returns a themed breadcrumb menu.
 *
 * @param $breadcrumb an array containing the breadcrumb links.
 *
 * @return a string containing the @a breadcrumb output.
 */
function theme_breadcrumb($breadcrumb) {
  return "<div class=\"breadcrumb\">". implode($breadcrumb, ' &raquo; ') ."</div>";
}

/**
 * Returns themed node.
 *
 * The passed $node object provides a all relevant information for displaying a
 * node:
 * @li @c $node->nid
 * @li @c $node->type i.e. story, blog, forum.
 * @li @c $node->title
 * @li @c $node->created a unix timestamp.
 * @li @c $node->teaser
 * @li @c $node->body
 * @li @c $node->changed a unix timestamp.
 * @li @c $node->uid the id of the poster.
 * @li @c $node->username the username of the poster.
 *
 * @param $node the @a node to be themed.
 * @param $main Display teaser only, as on main page?
 * @param $page Display node as standalone page (no title)?
 *
 * @return a string containing the @a node output.
 */
function theme_node($node, $main = 0, $page = 0) {
  if (module_exist("taxonomy")) {
    $terms = taxonomy_link("taxonomy terms", $node);
  }

  if ($page == 0) {
    $output = "<h2 class=\"title\">$node->title</h2> by ". format_name($node);
  }
  else {
    $output = "by ". format_name($node);
  }

  if (count($terms)) {
    $output .= " <small>(". theme('links', $terms) .")</small><br />";
  }

  if ($main && $node->teaser) {
    $output .= $node->teaser;
  }
  else {
    $output .= $node->body;
  }

  if ($links = link_node($node, $main)) {
    $output .= "<div class=\"links\">". theme('links', $links) ."</div>";
  }

  return $output;
}

/**
 * Returns themed form element.
 *
 * @param $title the form element's title
 * @param $value the form element's data
 * @param $description the form element's description or explanation
 * @param $id the form element's ID used by the &lt;label&gt; tag
 *
 * @return a string representing the form element
 */

function theme_form_element($title, $value, $description = NULL, $id = NULL) {

  if ($title) {
    if ($id) {
      $title = "<label for=\"$id\">$title:</label><br />";
    }
    else {
      $title = "<label>$title:</label><br />";
    }
  }
  else {
    if ($id) {
      $title = "<label for=\"$id\"></label>";
      // TODO: does this make sense to do?
    }
  }

  if ($description) {
    $description = "<div class=\"description\">$description</div>";
  }

  return "<div class=\"form-item\">". $title . $value . $description ."</div>\n";
}

/**
 * Returns themed table.
 *
 * @param $header array of table header strings
 * @param $rows array of arrays containing the table cells
 * @param $attributes attribute array for the table tag
 *
 * @return a string representing the table.
 */

function theme_table($header, $rows, $attributes = NULL) {

  $output = "<table ". drupal_attributes($attributes) .">\n";

  /*
  ** Emit the table header:
  */

  if (is_array($header)) {
    $output .= " <tr>";
    foreach ($header as $cell) {
      if (is_array($cell) && $cell['field']) {
        $cell = tablesort($cell, $header);
      }
      $output .= _theme_table_cell($cell, 1);
    }
    $output .= " </tr>\n";
  }

  /*
  ** Emit the table rows:
  */

  if (is_array($rows)) {
    foreach ($rows as $number => $row) {
      if ($number % 2 == 1) {
        $output .= " <tr class=\"light\">";
      }
      else {
        $output .= " <tr class=\"dark\">";
      }

      foreach ($row as $cell) {
        $output .= _theme_table_cell($cell, 0);
      }
      $output .= " </tr>\n";
    }
  }

  $output .= "</table>\n";

  return $output;
}

/**
 * Returns themed box.
 *
 * @param $title the @a subject of the box.
 * @param $content the @a content of the box.
 * @param $region the @a region of the box.
 *
 * @return a string containing the @a box output.
 */
function theme_box($title, $content, $region = 'main') {
  $output = "<h2 class=\"title\">$title</h2><div>$content</div>";
  return $output;
}

/**
 * Returns a themed block.
 *
 * You can style your blocks by defining .block (all blocks),
 * .block-<i>module</i> (all blocks of module <i>module</i>), and
 * \#block-<i>module</i>-<i>delta</i> (specific block of module <i>module</i>
 * with delta <i>delta</i>) in your theme's CSS.
 *
 * @param $block object "indexed with" fields from database table 'blocks'
 *   ($block->module, $block->delta, $block->region, ...) and fields returned by
 *   <i>module</i>_block("view") ($block->subject, $block->content, ...).
 *
 * @return a string containing the @a box output.
 */
function theme_block($block) {
  $output  = "<div class=\"block block-$block->module\" id=\"block-$block->module-$block->delta\">\n";
  $output .= " <h2 class=\"title\">$block->subject</h2>\n";
  $output .= " <div class=\"content\">$block->content</div>\n";
  $output .= "</div>\n";
  return $output;
}

/**
 * Returns themed marker, useful for marking new comments or required form
 * elements.
 *
 * @return a string containing the @a mark output.
 */
function theme_mark() {
  return '<span class="marker">*</span>';
}

/**
 * Returns themed list of items.
 *
 * @param $items (optional) an array of the items to be displayed in a list.
 * @param $title (optional) the title of the list.
 *
 * @return a string containing the @a list output.
 */
function theme_item_list($items = array(), $title = NULL) {
  $output = "<div class=\"item-list\">";
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
 * Returns themed error message.
 *
 * @param $message the error message to be themed.
 *
 * @return a string containing the @a error output.
 */
function theme_error($message) {
  return "<div class=\"error\">$message</div>";
}

/**
 * Returns code that emits an XML-icon.
 *
 * @return a string containing the @a output.
 */
function theme_xml_icon($url) {
  return "<div class=\"xml-icon\"><a href=\"$url\"><img src=\"misc/xml.gif\" width=\"36\" height=\"14\" alt=\"". t('XML feed') ."\" /></a></div>";
}

/**
 * Execute hook _footer() which is run at the end of the page right before the
 * \</body> tag.
 *
 * @param $main (optional)
 *
 * @return a string containing the @a closure output.
 */
function theme_closure($main = 0) {
  $footer = module_invoke_all('footer', $main);
  return implode($footer, "\n");
}

/**
 * Call _onload hook in all modules to enable modules to insert javascript that
 * will get run once the page has been loaded by the browser.
 *
 * @param $theme_onloads (optional) addition onload directives.
 *
 * @return a string containing the @a onload output.
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
  return '';
}

/**
 * Returns themed blocks available for current $user at $region.
 *
 * @param $region main|left|right
 *
 * @return a string containing the @a blocks output.
 */
function theme_blocks($region) {
  $output = '';

  if ($list = module_invoke('block', 'list', $region)) {
    foreach ($list as $key => $block) {
      // $key == <i>module</i>_<i>delta</i>
      $output .= theme('block', $block);
    }
  }
  return $output;
}
/* @} */

function _theme_table_cell($cell, $header = 0) {
  if (is_array($cell)) {
    $data = $cell['data'];
    foreach ($cell as $key => $value) {
      if ($key != 'data')  {
        $attributes .= " $key=\"$value\"";
      }
    }
  }
  else {
    $data = $cell;
  }

  if ($header) {
    $output = "<th$attributes>$data</th>";
  }
  else {
    $output = "<td$attributes>$data</td>";
  }

  return $output;
}
?>
