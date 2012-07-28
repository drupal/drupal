<?php

/**
 * @file
 * Built in plugins for Views output handling.
 */

use Drupal\views\ViewsObject;

/**
 * Builds and return a list of all plugins available in the system.
 *
 * @return Nested array of plugins, grouped by type.
 */
function views_discover_plugins() {
  $cache = array('display' => array(), 'style' => array(), 'row' => array(), 'argument default' => array(), 'argument validator' => array(), 'access' => array(), 'cache' => array(), 'exposed_form' => array());
  // Get plugins from all mdoules.
  foreach (module_implements('views_plugins') as $module) {
    $function = $module . '_views_plugins';
    $result = $function();
    if (!is_array($result)) {
      continue;
    }

    $module_dir = isset($result['module']) ? $result['module'] : $module;
    // Setup automatic path/file finding for theme registration
    if ($module_dir == 'views') {
      $theme_path = drupal_get_path('module', $module_dir) . '/theme';
      $theme_file = 'theme.inc';
      $path = drupal_get_path('module', $module_dir) . '/plugins';
    }
    else {
      $theme_path = $path = drupal_get_path('module', $module_dir);
      $theme_file = "$module.views.inc";
    }

    foreach ($result as $type => $info) {
      if ($type == 'module') {
        continue;
      }
      foreach ($info as $plugin => $def) {
        $def['module'] = $module_dir;
        if (!isset($def['theme path'])) {
          $def['theme path'] = $theme_path;
        }
        if (!isset($def['theme file'])) {
          $def['theme file'] = $theme_file;
        }
        if (!isset($def['path'])) {
          $def['path'] = $path;
        }
//        if (!isset($def['file'])) {
//          $def['file'] = $def['handler'] . '.inc';
//        }
        if (!isset($def['parent'])) {
          $def['parent'] = 'parent';
        }
        // Set the internal name to be able to read it out later.
        $def['name'] = $plugin;

        // merge the new data in
        $cache[$type][$plugin] = $def;
      }
    }
  }

  // Let other modules modify the plugins.
  drupal_alter('views_plugins', $cache);
  return $cache;
}

/**
 * Abstract base class to provide interface common to all plugins.
 */
abstract class views_plugin extends ViewsObject {
  /**
   * The top object of a view.
   *
   * @var view
   */
  var $view = NULL;

  /**
   * The current used views display.
   *
   * @var views_display
   */
  var $display = NULL;

  /**
   * The plugin type of this plugin, for example style or query.
   */
  var $plugin_type = NULL;

  /**
   * The plugin name of this plugin, for example table or full.
   */
  var $plugin_name = NULL;

  /**
   * Init will be called after construct, when the plugin is attached to a
   * view and a display.
   */

  /**
   * Provide a form to edit options for this plugin.
   */
  function options_form(&$form, &$form_state) {
    // Some form elements belong in a fieldset for presentation, but can't
    // be moved into one because of the form_state['values'] hierarchy. Those
    // elements can add a #fieldset => 'fieldset_name' property, and they'll
    // be moved to their fieldset during pre_render.
    $form['#pre_render'][] = 'views_ui_pre_render_add_fieldset_markup';
  }

  /**
   * Validate the options form.
   */
  function options_validate(&$form, &$form_state) { }

  /**
   * Handle any special handling on the validate form.
   */
  function options_submit(&$form, &$form_state) { }

  /**
   * Add anything to the query that we might need to.
   */
  function query() { }

  /**
   * Provide a full list of possible theme templates used by this style.
   */
  function theme_functions() {
    return views_theme_functions($this->definition['theme'], $this->view, $this->display);
  }

  /**
   * Provide a list of additional theme functions for the theme information page
   */
  function additional_theme_functions() {
    $funcs = array();
    if (!empty($this->definition['additional themes'])) {
      foreach ($this->definition['additional themes'] as $theme => $type) {
        $funcs[] = views_theme_functions($theme, $this->view, $this->display);
      }
    }
    return $funcs;
  }

  /**
   * Validate that the plugin is correct and can be saved.
   *
   * @return
   *   An array of error strings to tell the user what is wrong with this
   *   plugin.
   */
  function validate() { return array(); }

  /**
   * Returns the summary of the settings in the display.
   */
  function summary_title() {
    return t('Settings');
  }
  /**
   * Return the human readable name of the display.
   *
   * This appears on the ui beside each plugin and beside the settings link.
   */
  function plugin_title() {
    if (isset($this->definition['short_title'])) {
      return check_plain($this->definition['short_title']);
    }
    return check_plain($this->definition['title']);
  }
}
