<?php
/* $Id$ */

/**
 * @file
 * The theme system, which controls the output of Drupal.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by user themes.
 *
 * @see <a href="http://drupal.org/node/253">Theme system</a>
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
  switch ($section) {
    case 'admin/themes#description':
      return t('The base theme');
  }
}

/**
 * Initialize the theme system by loading the theme.
 *
 * @return
 *   The name of the currently selected theme.
 */
function init_theme() {
  global $user;

  $themes = list_themes();

  // Only select the user selected theme if it is available in the
  // list of enabled themes.
  $theme = $user->theme && $themes[$user->theme] ? $user->theme : variable_get('theme_default', 0);

  include_once($themes[$theme]->filename);

  return $theme;
}

/**
 * Provides a list of currently available themes.
 *
 * @param $refresh
 *   Whether to reload the list of themes from the database.
 * @return
 *   An array of the currently available themes.
 */
function list_themes($refresh = FALSE) {
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
 * Generate the themed representation of a Drupal object.
 *
 * All requests for themed functions must go through this function. It examines
 * the request and routes it to the appropriate theme function. If the current
 * theme does not implement the requested function, then the base theme function
 * is called.
 *
 * For example, to retrieve the HTML that is output by theme_page($output), a
 * module should call theme('page', $output).
 *
 * @param $function
 *   The name of the theme function to call.
 * @param ...
 *   Additional arguments to pass along to the theme function.
 * @return
 *   An HTML string that generates the themed output.
 */
function theme() {
  global $theme;

  $args = func_get_args();
  $function = array_shift($args);

  if (($theme != '') && (function_exists($theme .'_'. $function))) {
    return call_user_func_array($theme .'_'. $function, $args);
  }
  elseif (function_exists('theme_'. $function)){
    return call_user_func_array('theme_'. $function, $args);
  }
}

/**
 * Return the path to the currently selected theme.
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
 * All functions that produce HTML for display should be themeable. This means
 * that they should be named with the theme_ prefix, and invoked using theme()
 * rather than being called directly. This allows themes to override the display
 * of any Drupal object.
 *
 * @see theme.inc
 */

/**
 * Return an entire Drupal page displaying the supplied content.
 *
 * @param $content
 *   A string to display in the main content area of the page.
 * @param $title
 *   The title of the page, if different from that provided by the menu system.
 * @param $breadcrumb
 *   The breadcrumb trail for the page, if different from that provided by the
 *   menu system. Use menu_set_location() instead, if possible.
 * @return
 *   A string containing the entire HTML page.
 */
function theme_page($content, $title = NULL, $breadcrumb = NULL) {
  if (isset($title)) {
    drupal_set_title($title);
  }

  if (isset($breadcrumb)) {
    drupal_set_breadcrumb($breadcrumb);
  }

  $output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
  $output .= '<html xmlns="http://www.w3.org/1999/xhtml">';
  $output .= '<head>';
  $output .= ' <title>'. (drupal_get_title() ? drupal_get_title() : variable_get('site_name', 'drupal')) .'</title>';
  $output .= drupal_get_html_head();

  $output .= ' </head>';
  $output .= ' <body style="background-color: #fff; color: #000;"'. theme('onload_attribute'). '">';
  $output .= '<table border="0" cellspacing="4" cellpadding="4"><tr><td style="vertical-align: top; width: 170px;">';

  $output .= theme('box', t('Navigation'), @implode('<br />', link_page()));
  $output .= theme('blocks', 'all');
  $output .= '</td><td style="vertical-align: top;">';

  $output .= theme('breadcrumb', drupal_get_breadcrumb());
  $output .= '<h1>' . drupal_get_title() . '</h1>';

  if ($tabs = theme('menu_local_tasks')) {
   $output .= $tabs;
  }

  if ($help = menu_get_active_help()) {
    $output .= '<small>'. $help .'</small><hr />';
  }

  $output .= theme_status_messages();

  $output .= "\n<!-- begin content -->\n";
  $output .= $content;
  $output .= "\n<!-- end content -->\n";

  $output .= '</td></tr></table>';
  $output .= theme_closure();
  $output .= '</body></html>';

  return $output;
}

/**
 * Returns themed set of status and/or error messages.  The messages are grouped
 * by type.
 *
 * @return
 *   A string containing the messages.
 */
function theme_status_messages() {
  if ($data = drupal_get_messages()) {
    $output = '';
    foreach ($data as $type => $messages) {
      $output .= "<div class=\"messages $type\">\n";
      if (count($messages) > 1) {
        $output .= " <ul>\n";
        foreach($messages as $message) {
          $output .= '  <li>'. $message ."</li>\n";
        }
        $output .= " </ul>\n";
      }
      else {
        $output .= $messages[0];
      }
      $output .= "</div>\n";
    }

    return $output;
  }
}

/**
 * Return a themed set of links.
 *
 * @param $links
 *   An array of links to be themed.
 * @param $delimiter
 *   A string used to separate the links.
 * @return
 *   A string containing the themed links.
 */
function theme_links($links, $delimiter = ' | ') {
  return implode($delimiter, $links);
}

/**
 * Return a themed image.
 *
 * @param $path
 *   The path of the image file.
 * @param $alt
 *   The alternative text for text-based browsers.
 * @param $title
 *   The title text is displayed when the image is hovered in some popular browsers.
 * @param $attr
 *   Attributes placed in the img tag.
 *   This parameter overrides the default auto-detection of width and height.
 * @return
 *   A string containing the image tag.
 */
function theme_image($path, $alt = NULL, $title = NULL, $attr = NULL) {
  if (isset($attr) || (file_exists($path) && (list($width, $height, $type, $attr) = getimagesize($path)))) {
    return "<img src=\"$path\" $attr alt=\"$alt\" title=\"$title\" />";
  }
}

/**
 * Return a themed breadcrumb trail.
 *
 * @param $breadcrumb
 *   An array containing the breadcrumb links.
 * @return a string containing the breadcrumb output.
 */
function theme_breadcrumb($breadcrumb) {
  return '<div class="breadcrumb">'. implode($breadcrumb, ' &raquo; ') .'</div>';
}

/**
 * Return a themed node.
 *
 * @param $node
 *   An object providing all relevant information for displaying a node:
 *   - $node->nid: The ID of the node.
 *   - $node->type: The content type (story, blog, forum...).
 *   - $node->title: The title of the node.
 *   - $node->created: The creation date, as a UNIX timestamp.
 *   - $node->teaser: A shortened version of the node body.
 *   - $node->body: The entire node contents.
 *   - $node->changed: The last modification date, as a UNIX timestamp.
 *   - $node->uid: The ID of the author.
 *   - $node->username: The username of the author.
 * @param $teaser
 *   Whether to display the teaser only, as on the main page.
 * @param $page
 *   Whether to display the node as a standalone page. If TRUE, do not display
 *   the title because it will be provided by the menu system.
 * @return
 *   A string containing the node output.
 */
function theme_node($node, $teaser = FALSE, $page = FALSE) {
  if (module_exist('taxonomy')) {
    $terms = taxonomy_link('taxonomy terms', $node);
  }

  if ($page == 0) {
    $output = '<h2 class="title">'. $node->title .'</h2> by '. format_name($node);
  }
  else {
    $output = 'by '. format_name($node);
  }

  if (count($terms)) {
    $output .= ' <small>('. theme('links', $terms) .')</small><br />';
  }

  if ($teaser && $node->teaser) {
    $output .= $node->teaser;
  }
  else {
    $output .= $node->body;
  }

  if ($links = link_node($node, $teaser)) {
    $output .= '<div class="links">'. theme('links', $links) .'</div>';
  }

  return $output;
}

/**
 * Return a themed form element.
 *
 * @param $title the form element's title
 * @param $value the form element's data
 * @param $description the form element's description or explanation
 * @param $id the form element's ID used by the &lt;label&gt; tag
 * @param $required a boolean to indicate whether this is a required field or not
 * @param $error a string with an error message filed against this form element
 *
 * @return a string representing the form element
 */
function theme_form_element($title, $value, $description = NULL, $id = NULL, $required = FALSE, $error = FALSE) {

  $output  = "<div class=\"form-item\">\n";
  $required = $required ? theme('mark') : '';

  if ($title) {
    if ($id) {
      $output .= " <label for=\"$id\">$title:</label>$required<br />\n";
    }
    else {
      $output .= " <label>$title:</label>$required<br />\n";
    }
  }

  $output .= " $value\n";

  if ($description) {
    $output .= " <div class=\"description\">$description</div>\n";
  }

  $output .= "</div>\n";

  return $output;
}


/**
 * Return a themed submenu, typically displayed under the tabs.
 *
 * @param $links
 *   An array of links.
 */
function theme_submenu($links) {
  return '<div class="submenu">'. implode(' | ', $links) .'</div>';
}

/**
 * Return a themed table.
 *
 * @param $header
 *   An array containing the table headers. Each element of the array can be
 *   either a localized string or an associative array with the following keys:
 *   - "data": The localized title of the table column.
 *   - "field": The database field represented in the table column (required if
 *     user is to be able to sort on this column).
 *   - "sort": A default sort order for this column ("asc" or "desc").
 *   - Any HTML attributes, such as "colspan", to apply to the column header cell.
 * @param $rows
 *   An array of arrays containing the table cells. Each cell can be either a
 *   string or and associative array with the following keys:
 *   - "data": The string to display in the table cell.
 *   - Any HTML attributes, such as "colspan", to apply to the table cell.
 * @param $attributes
 *   An array of HTML attributes to apply to the table tag.
 * @return
 *   An HTML string representing the table.
 */
function theme_table($header, $rows, $attributes = NULL) {

  $output = '<table '. drupal_attributes($attributes) .">\n";

  // Format the table header:
  if (is_array($header)) {
    $ts = tablesort_init($header);
    $output .= ' <tr>';
    foreach ($header as $cell) {
      $cell = tablesort_header($cell, $header, $ts);
      $output .= _theme_table_cell($cell, 1);
    }
    $output .= " </tr>\n";
  }

  // Format the table rows:
  if (is_array($rows)) {
    foreach ($rows as $number => $row) {
      if ($number % 2 == 1) {
        $output .= ' <tr class="light">';
      }
      else {
        $output .= ' <tr class="dark">';
      }

      $i = 0;
      foreach ($row as $cell) {
        $cell = tablesort_cell($cell, $header, $ts, $i);
        $output .= _theme_table_cell($cell, 0);
        $i++;
      }
      $output .= " </tr>\n";
    }
  }

  $output .= "</table>\n";
  return $output;
}

/**
 * Return a themed box.
 *
 * @param $title
 *   The subject of the box.
 * @param $content
 *   The content of the box.
 * @param $region
 *   The region in which the box is displayed.
 * @return
 *   A string containing the box output.
 */
function theme_box($title, $content, $region = 'main') {
  $output = '<h2 class="title">'. $title .'</h2><div>'. $content .'</div>';
  return $output;
}

/**
 * Return a themed block.
 *
 * You can style your blocks by defining .block (all blocks),
 * .block-<i>module</i> (all blocks of module <i>module</i>), and
 * \#block-<i>module</i>-<i>delta</i> (specific block of module <i>module</i>
 * with delta <i>delta</i>) in your theme's CSS.
 *
 * @param $block
 *   An object populated with fields from the "blocks" database table
 *   ($block->module, $block->delta, $block->region, ...) and fields returned by
 *   <i>module</i>_block('view') ($block->subject, $block->content, ...).
 * @return
 *   A string containing the block output.
 */
function theme_block($block) {
  $output  = "<div class=\"block block-$block->module\" id=\"block-$block->module-$block->delta\">\n";
  $output .= " <h2 class=\"title\">$block->subject</h2>\n";
  $output .= " <div class=\"content\">$block->content</div>\n";
  $output .= "</div>\n";
  return $output;
}

/**
 * Return a themed marker, useful for marking new comments or required form
 * elements.
 *
 * @return
 *   A string containing the marker.
 */
function theme_mark() {
  return '<span class="marker">*</span>';
}

/**
 * Return a themed list of items.
 *
 * @param $items
 *   An array of items to be displayed in the list.
 * @param $title
 *   The title of the list.
 * @return
 *   A string containing the list output.
 */
function theme_item_list($items = array(), $title = NULL) {
  $output = '<div class="item-list">';
  if (isset($title)) {
    $output .= '<h3>'. $title .'</h3>';
  }

  if (isset($items)) {
    $output .= '<ul>';
    foreach ($items as $item) {
      $output .= '<li>'. $item .'</li>';
    }
    $output .= '</ul>';
  }
  $output .= '</div>';
  return $output;
}

/**
 * Return a themed error message.
 * REMOVE: this function is deprecated an no longer used in core.
 *
 * @param $message
 *   The error message to be themed.
 *
 * @return
 *   A string containing the error output.
 */
function theme_error($message) {
  return '<div class="error">'. $message .'</div>';
}

function theme_more_help_link($url) {
  return '<div class="more-help-link">' . t('[<a href="%link">more help...</a>]', array('%link' => $url)) . '</div><hr />';
}

/**
 * Return code that emits an XML icon.
 */
function theme_xml_icon($url) {
  if ($image = theme('image', 'misc/xml.png', t('XML feed'), t('XML feed'))) {
    return '<div class="xml-icon"><a href="'. $url .'">'. $image. '</a></div>';
  }
}

/**
 * Execute hook_footer() which is run at the end of the page right before the
 * close of the body tag.
 *
 * @param $main (optional)
 *
 * @return
 *   A string containing the results of the hook_footer() calls.
 */
function theme_closure($main = 0) {
  $footer = module_invoke_all('footer', $main);
  return implode($footer, "\n");
}

/**
 * Call hook_onload() in all modules to enable modules to insert JavaScript that
 * will get run once the page has been loaded by the browser.
 *
 * @param $theme_onloads
 *   Additional onload directives.
 * @return
 *   A string containing the onload attributes.
 */
function theme_onload_attribute($theme_onloads = array()) {
  if (!is_array($theme_onloads)) {
    $theme_onloads = array($theme_onloads);
  }
  // Merge theme onloads (javascript rollovers, image preloads, etc.)
  // with module onloads (htmlarea, etc.)
  $onloads = array_merge(module_invoke_all('onload'), $theme_onloads);
  if (count($onloads)) {
    return ' onload="' . implode('; ', $onloads) . '"';
  }
  return '';
}

/**
 * Return a set of blocks available for the current user.
 *
 * @param $region
 *   Which set of blocks to retrieve.
 * @return
 *   A string containing the themed blocks for this region.
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
/**
 * @} end of defgroup themeable
 */

function _theme_table_cell($cell, $header = 0) {
  $attributes = '';

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
