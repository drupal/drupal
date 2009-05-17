<?php

/**
 * @file
 * Built in plugins for Views output handling.
 */

// @todo: Remove this once update.php can use the registry
views_include('base');

/**
 * Implements hook_views_plugins().
 */
function views_views_plugins() {
  $js_path = drupal_get_path('module', 'ctools') . '/js';
  $plugins = array(
    // display, style, row, argument default, argument validator and access.
    'display' => array(
      // Default settings for all display plugins.
      'default' => array(
        'title' => t('Master'),
        'help' => t('Default settings for this view.'),
        'handler' => 'views_plugin_display_default',
        'theme' => 'views_view',
        'no ui' => TRUE,
        'no remove' => TRUE,
        'js' => array('misc/form.js', 'misc/collapse.js', 'misc/textarea.js', 'misc/tabledrag.js', 'misc/autocomplete.js', "$js_path/dependent.js"),
        'use ajax' => TRUE,
        'use pager' => TRUE,
        'use more' => TRUE,
        'accept attachments' => TRUE,
        'help topic' => 'display-default',
      ),
      'page' => array(
        'title' => t('Page'),
        'help' => t('Display the view as a page, with a URL and menu links.'),
        'handler' => 'views_plugin_display_page',
        'theme' => 'views_view',
        'uses hook menu' => TRUE,
        'contextual links locations' => array('page'),
        'use ajax' => TRUE,
        'use pager' => TRUE,
        'use more' => TRUE,
        'accept attachments' => TRUE,
        'admin' => t('Page'),
        'help topic' => 'display-page',
      ),
      'block' => array(
        'title' => t('Block'),
        'help' => t('Display the view as a block.'),
        'handler' => 'views_plugin_display_block',
        'theme' => 'views_view',
        'uses hook block' => TRUE,
        'contextual links locations' => array('block'),
        'use ajax' => TRUE,
        'use pager' => TRUE,
        'use more' => TRUE,
        'accept attachments' => TRUE,
        'admin' => t('Block'),
        'help topic' => 'display-block',
      ),
      'attachment' => array(
        'title' => t('Attachment'),
        'help' => t('Attachments added to other displays to achieve multiple views in the same view.'),
        'handler' => 'views_plugin_display_attachment',
        'theme' => 'views_view',
        'contextual links locations' => array(),
        'use ajax' => TRUE,
        'use pager' => FALSE,
        'use more' => TRUE,
        'accept attachments' => FALSE,
        'help topic' => 'display-attachment',
      ),
      'feed' => array(
        'title' => t('Feed'),
        'help' => t('Display the view as a feed, such as an RSS feed.'),
        'handler' => 'views_plugin_display_feed',
        'uses hook menu' => TRUE,
        'use ajax' => FALSE,
        'use pager' => FALSE,
        'accept attachments' => FALSE,
        'admin' => t('Feed'),
        'help topic' => 'display-feed',
      ),
      'embed' => array(
        'title' => t('Embed'),
        'help' => t('Provide a display which can be embedded using the views api.'),
        'handler' => 'views_plugin_display_embed',
        'uses hook menu' => FALSE,
        'use ajax' => TRUE,
        'use pager' => TRUE,
        'accept attachments' => FALSE,
        'admin' => t('Embed'),
        'no ui' => !variable_get('views_ui_display_embed', FALSE),
      ),
    ),
    'display_extender' => array(
      // Default settings for all display_extender plugins.
      'default' => array(
        'title' => t('Empty display extender'),
        'help' => t('Default settings for this view.'),
        'handler' => 'views_plugin_display_extender',
        // You can force the plugin to be enabled
        'enabled' => FALSE,
        'no ui' => TRUE,
      ),
    ),
    'style' => array(
      // Default settings for all style plugins.
      'default' => array(
        'title' => t('Unformatted list'),
        'help' => t('Displays rows one after another.'),
        'handler' => 'views_plugin_style_default',
        'theme' => 'views_view_unformatted',
        'uses row plugin' => TRUE,
        'uses row class' => TRUE,
        'uses grouping' => TRUE,
        'uses options' => TRUE,
        'type' => 'normal',
        'help topic' => 'style-unformatted',
      ),
      'list' => array(
        'title' => t('HTML list'),
        'help' => t('Displays rows as an HTML list.'),
        'handler' => 'views_plugin_style_list',
        'theme' => 'views_view_list',
        'uses row plugin' => TRUE,
        'uses row class' => TRUE,
        'uses options' => TRUE,
        'type' => 'normal',
        'help topic' => 'style-list',
      ),
      'grid' => array(
        'title' => t('Grid'),
        'help' => t('Displays rows in a grid.'),
        'handler' => 'views_plugin_style_grid',
        'theme' => 'views_view_grid',
        'uses fields' => FALSE,
        'uses row plugin' => TRUE,
        'uses row class' => TRUE,
        'uses options' => TRUE,
        'type' => 'normal',
        'help topic' => 'style-grid',
      ),
      'table' => array(
        'title' => t('Table'),
        'help' => t('Displays rows in a table.'),
        'handler' => 'views_plugin_style_table',
        'theme' => 'views_view_table',
        'uses row plugin' => FALSE,
        'uses row class' => TRUE,
        'uses fields' => TRUE,
        'uses options' => TRUE,
        'type' => 'normal',
        'help topic' => 'style-table',
      ),
      'default_summary' => array(
        'title' => t('List'),
        'help' => t('Displays the default summary as a list.'),
        'handler' => 'views_plugin_style_summary',
        'theme' => 'views_view_summary',
        'type' => 'summary', // only shows up as a summary style
        'uses options' => TRUE,
        'help topic' => 'style-summary',
      ),
      'unformatted_summary' => array(
        'title' => t('Unformatted'),
        'help' => t('Displays the summary unformatted, with option for one after another or inline.'),
        'handler' => 'views_plugin_style_summary_unformatted',
        'theme' => 'views_view_summary_unformatted',
        'type' => 'summary', // only shows up as a summary style
        'uses options' => TRUE,
        'help topic' => 'style-summary-unformatted',
      ),
      'rss' => array(
        'title' => t('RSS Feed'),
        'help' => t('Generates an RSS feed from a view.'),
        'handler' => 'views_plugin_style_rss',
        'theme' => 'views_view_rss',
        'uses row plugin' => TRUE,
        'uses options' => TRUE,
        'type' => 'feed',
        'help topic' => 'style-rss',
      ),
    ),
    'row' => array(
      'fields' => array(
        'title' => t('Fields'),
        'help' => t('Displays the fields with an optional template.'),
        'handler' => 'views_plugin_row_fields',
        'theme' => 'views_view_fields',
        'uses fields' => TRUE,
        'uses options' => TRUE,
        'type' => 'normal',
        'help topic' => 'style-row-fields',
      ),
      'rss_fields' => array(
        'title' => t('Fields'),
        'help' => t('Display fields as RSS items.'),
        'handler' => 'views_plugin_row_rss_fields',
        'theme' => 'views_view_row_rss',
        'uses fields' => TRUE,
        'uses options' => TRUE,
        'type' => 'feed',
        'help topic' => 'style-row-fields',
      ),
    ),
    'argument default' => array(
      'parent' => array(
        'no ui' => TRUE,
        'handler' => 'views_plugin_argument_default',
        'parent' => '',
      ),
      'fixed' => array(
        'title' => t('Fixed value'),
        'handler' => 'views_plugin_argument_default_fixed',
      ),
      'php' => array(
        'title' => t('PHP Code'),
        'handler' => 'views_plugin_argument_default_php',
      ),
      'raw' => array(
        'title' => t('Raw value from URL'),
        'handler' => 'views_plugin_argument_default_raw',
      ),
    ),
    'argument validator' => array(
      'php' => array(
        'title' => t('PHP Code'),
        'handler' => 'views_plugin_argument_validate_php',
      ),
      'numeric' => array(
        'title' => t('Numeric'),
        'handler' => 'views_plugin_argument_validate_numeric',
      ),
    ),
    'access' => array(
      'none' => array(
        'title' => t('None'),
        'help' => t('Will be available to all users.'),
        'handler' => 'views_plugin_access_none',
        'help topic' => 'access-none',
      ),
      'role' => array(
        'title' => t('Role'),
        'help' => t('Access will be granted to users with any of the specified roles.'),
        'handler' => 'views_plugin_access_role',
        'uses options' => TRUE,
        'help topic' => 'access-role',
      ),
      'perm' => array(
        'title' => t('Permission'),
        'help' => t('Access will be granted to users with the specified permission string.'),
        'handler' => 'views_plugin_access_perm',
        'uses options' => TRUE,
        'help topic' => 'access-perm',
      ),
    ),
    'query' => array(
      'parent' => array(
        'no ui' => TRUE,
        'handler' => 'views_plugin_query',
        'parent' => '',
      ),
      'views_query' => array(
        'title' => t('SQL Query'),
        'help' => t('Query will be generated and run using the Drupal database API.'),
        'handler' => 'views_plugin_query_default'
      ),
    ),
    'cache' => array(
      'parent' => array(
        'no ui' => TRUE,
        'handler' => 'views_plugin_cache',
        'parent' => '',
      ),
      'none' => array(
        'title' => t('None'),
        'help' => t('No caching of Views data.'),
        'handler' => 'views_plugin_cache_none',
        'help topic' => 'cache-none',
      ),
      'time' => array(
        'title' => t('Time-based'),
        'help' => t('Simple time-based caching of data.'),
        'handler' => 'views_plugin_cache_time',
        'uses options' => TRUE,
        'help topic' => 'cache-time',
      ),
    ),
    'exposed_form' => array(
      'parent' => array(
        'no ui' => TRUE,
        'handler' => 'views_plugin_exposed_form',
        'parent' => '',
      ),
      'basic' => array(
        'title' => t('Basic'),
        'help' => t('Basic exposed form'),
        'handler' => 'views_plugin_exposed_form_basic',
        'uses options' => TRUE,
        'help topic' => 'exposed-form-basic',
      ),
      'input_required' => array(
        'title' => t('Input required'),
        'help' => t('An exposed form that only renders a view if the form contains user input.'),
        'handler' => 'views_plugin_exposed_form_input_required',
        'uses options' => TRUE,
        'help topic' => 'exposed-form-input-required',
      ),
    ),
    'pager' => array(
      'parent' => array(
        'no ui' => TRUE,
        'handler' => 'views_plugin_pager',
        'parent' => '',
      ),
      'none' => array(
        'title' => t('Display all items'),
        'help' => t("Display all items that this view might find"),
        'handler' => 'views_plugin_pager_none',
        'help topic' => 'pager-none',
        'uses options' => TRUE,
        'type' => 'basic',
      ),
      'some' => array(
        'title' => t('Display a specified number of items'),
        'help' => t('Display a limited number items that this view might find.'),
        'handler' => 'views_plugin_pager_some',
        'help topic' => 'pager-some',
        'uses options' => TRUE,
        'type' => 'basic',
      ),
      'full' => array(
        'title' => t('Paged output, full pager'),
        'short title' => t('Full'),
        'help' => t('Paged output, full Drupal style'),
        'handler' => 'views_plugin_pager_full',
        'help topic' => 'pager-full',
        'uses options' => TRUE,
      ),
      'mini' => array(
        'title' => t('Paged output, mini pager'),
        'short title' => t('Mini'),
        'help' => t('Use the mini pager output.'),
        'handler' => 'views_plugin_pager_mini',
        'help topic' => 'pager-mini',
        'uses options' => TRUE,
        'parent' => 'full',
      ),
    ),
    'localization' => array(
      'parent' => array(
        'no ui' => TRUE,
        'handler' => 'views_plugin_localization',
        'parent' => '',
      ),
     'none' => array(
        'title' => t('None'),
        'help' => t('Do not pass admin strings for translation.'),
        'handler' => 'views_plugin_localization_none',
        'help topic' => 'localization-none',
      ),
      'core' => array(
        'title' => t('Core'),
        'help' => t("Use Drupal core t() function. Not recommended, as it doesn't support updates to existing strings."),
        'handler' => 'views_plugin_localization_core',
        'help topic' => 'localization-core',
      ),
    ),
  );
  // Add a help message pointing to the i18views module if it is not present.
  if (!module_exists('i18nviews')) {
    $plugins['localization']['core']['help'] .= ' ' . t('If you need to translate Views labels into other languages, consider installing the <a href="!path">Internationalization</a> package\'s Views translation module.', array('!path' => url('http://drupal.org/project/i18n', array('absolute' => TRUE))));
  }

  if (module_invoke('ctools', 'api_version', '1.3')) {
    $plugins['style']['jump_menu_summary'] = array(
      'title' => t('Jump menu'),
      'help' => t('Puts all of the results into a select box and allows the user to go to a different page based upon the results.'),
      'handler' => 'views_plugin_style_summary_jump_menu',
      'theme' => 'views_view_summary_jump_menu',
      'type' => 'summary', // only shows up as a summary style
      'uses options' => TRUE,
      'help topic' => 'style-summary-jump-menu',
    );
    $plugins['style']['jump_menu'] = array(
      'title' => t('Jump menu'),
      'help' => t('Puts all of the results into a select box and allows the user to go to a different page based upon the results.'),
      'handler' => 'views_plugin_style_jump_menu',
      'theme' => 'views_view_jump_menu',
      'uses row plugin' => TRUE,
      'uses fields' => TRUE,
      'uses options' => TRUE,
      'type' => 'normal',
      'help topic' => 'style-jump-menu',
    );
  }

  return $plugins;
}

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
        if (!isset($def['file'])) {
          $def['file'] = $def['handler'] . '.inc';
        }
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
class views_plugin extends views_object {
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
    if (isset($this->definition['short title'])) {
      return check_plain($this->definition['short title']);
    }
    return check_plain($this->definition['title']);
  }
}

/**
 * Get enabled display extenders.
 */
function views_get_enabled_display_extenders() {
  $enabled = array_filter(variable_get('views_display_extenders', array()));
  $options = views_fetch_plugin_names('display_extender');
  foreach ($options as $name => $plugin) {
    $enabled[$name] = $name;
  }

  return $enabled;
}
