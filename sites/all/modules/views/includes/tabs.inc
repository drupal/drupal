<?php
// $Id: tabs.inc,v 1.7.6.3 2010/03/20 23:16:37 dereine Exp $
/**
 * @file
 *
 * Classes and theme functions for rendering javascript UI tabs.
 */

/**
 * Contain a set of tabs as well as the ability to render them.
 *
 * There are three 'areas' of a tabset.
 * - title: The clickable link to display the tab area. These are always visible.
 * - body: The actual HTML body of the tab. Only one body is visible at a time.
 * - extra: An optional decorative area around the tabs.
 */
class views_tabset {
  var $tabs = array();
  var $extra = '';
  var $selected = NULL;

  /**
   * Add a tab to the tabset.
   *
   * @param $name
   *   The name of the tab; this is the internal identifier and must be
   *   unique within the tabset.
   * @param $title
   *   If given, this will be the visible title of the tab. This can also
   *   be set via $tabset->set(). This will be the link to click on to
   *   view the tab.
   * @param $body
   *   If given, this is the body of the tab itself. It will display
   *   when the tab title is clicked on.
   */
  function add($name, $title = '', $body = '') {
    if (is_object($name) && is_subclass_of($name, 'views_tab')) {
      $this->add_tab($name);
    }
    elseif (is_array($name)) {
      foreach ($name as $real_tab) {
        $this->add($real_tab);
      }
    }
    else {
      $this->add_tab(new views_tab($name, $title, $body));
    }
  }

  /**
   * Add a fully realized tab object to the tabset.
   *
   * @param $tab
   *   A fully populated views_tab object.
   */
  function add_tab($tab) {
    $this->tabs[$tab->name] = $tab;
  }

  /**
   * Set the values of a tab.
   *
   * @param $name
   *   The unique identifier of the tab to set.
   * @param $title
   *   The title of the tab; this will be clickable.
   * @param $body
   *   The HTML body of the tab.
   */
  function set($name, $title, $body = NULL) {
    if (empty($this->tabs[$name])) {
      return $this->add($name, $title, $body);
    }
    $this->tabs[$name]->title = $title;
    if (isset($body)) {
      $this->tabs[$name]->body = $body;
    }
  }

  /**
   * Set the body of a tab.
   */
  function set_body($name, $body) {
    if (empty($this->tabs[$name])) {
      return $this->add($name, '', $body);
    }
    $this->tabs[$name]->body = $body;
  }

  /**
   * Add text to the 'extra' region of the tabset.
   */
  function add_extra($text) {
    $this->extra .= $text;
  }

  /**
   * Remove a tab.
   *
   * @param $tab
   *   May be the name of the tab or a views_tab object.
   */
  function remove($tab) {
    if (is_string($tab)) {
      unset($this->tabs[$tab]);
    }
    else {
      unset($this->tabs[$tab->name]);
    }
  }

  /**
   * Control which tab will be selected when it is rendered.
   */
  function set_selected($name) {
    $this->selected = $name;
  }

  /**
   * Output the HTML for the tabs.
   *
   * @return
   *   HTML representation of the tabs.
   */
  function render() {
    views_add_js('tabs');
    views_add_css('views-tabs');

    if (empty($this->selected)) {
      $keys = array_keys($this->tabs);
      $this->selected = array_shift($keys);
    }

    drupal_alter('views_tabset', $this);
    return theme('views_tabset', array('tabs' => $this->tabs, 'extra' => $this->extra, 'selected' => $this->selected));
  }
}

/**
 * An object to represent an individual tab within a tabset.
 */
class views_tab {
  var $title;
  var $body;
  var $name;

  /**
   * Construct a new tab.
   */
  function views_tab($name, $title, $body = NULL) {
    $this->name = $name;
    $this->title = $title;
    $this->body = $body;
  }

  /**
   * Generate HTML output for a tab.
   */
  function render() {
    return theme('views_tab', array('body' => $this->body));
  }
}

/**
 * Render a tabset.
 *
 * @todo Turn this into a template.
 */
function theme_views_tabset($variables) {
  $tabs = $variables['tabs'];
  $extra = isset($variables['extra']) ? $variables['extra'] : NULL;
  $selected = isset($variables['selected']) ? $variables['selected'] : NULL;
  $link_output = "<div class=\"views-tabs\"><ul id=\"views-tabset\">\n";
  $tab_output = "<div class=\"views-tab-area\">\n";

  foreach ($tabs as $name => $tab) {
    $link_output .= '<li' . ($name == $selected ? ' class="active"': '') . '><a href="#views-tab-' . $tab->name . '" id="views-tab-title-' . $tab->name . '">' . check_plain($tab->title) . '</a></li>' . "\n";
    $tab_output .= '<div id="views-tab-' . $tab->name . '" class="views-tab">' . $tab->render() . "</div>\n";
  }
  $link_output .= "</ul>\n";

  if ($extra) {
    $link_output .= "<div class=\"extra\">$extra</div>\n";
  }

  $link_output .= "</div>\n";
  $tab_output .= "</div>\n";
  return '<div class="views-tabset clearfix">' . $link_output . $tab_output . '</div>';
}

/**
 * Theme a simple tab.
 */
function theme_views_tab($variables) {
  return $variables['body'];
}
