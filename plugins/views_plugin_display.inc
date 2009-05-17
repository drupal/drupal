<?php

/**
 * @file
 * Contains the base display plugin.
 */

/**
 * @defgroup views_display_plugins Views display plugins
 * @{
 * Display plugins control how Views interact with the rest of Drupal.
 *
 * They can handle creating Views from a Drupal page hook; they can
 * handle creating Views from a Drupal block hook. They can also
 * handle creating Views from an external module source, such as
 * a Panels pane, or an insert view, or a CCK field type.
 *
 * @see hook_views_plugins()
 */

/**
 * The default display plugin handler. Display plugins handle options and
 * basic mechanisms for different output methods.
 */
class views_plugin_display extends views_plugin {
  /**
   * The top object of a view.
   *
   * @var view
   */
  var $view = NULL;

  var $handlers = array();

  /**
   * Stores all available display extenders.
   */
  var $extender = array();

  function init(&$view, &$display, $options = NULL) {
    $this->view = &$view;
    $this->display = &$display;

    // Load extenders as soon as possible.
    $this->extender = array();
    $extenders = views_get_enabled_display_extenders();
    // If you update to the dev version the registry might not be loaded yet.
    if (!empty($extenders) && class_exists('views_plugin_display_extender')) {
      foreach ($extenders as $extender) {
        $plugin = views_get_plugin('display_extender', $extender);
        if ($plugin) {
          $plugin->init($this->view, $this);
          $this->extender[$extender] = $plugin;
        }
        else {
          vpr('Invalid display extender @extender', array('@handler' => $extender));
        }
      }
    }

    // Track changes that the user should know about.
    $changed = FALSE;

    // Make some modifications:
    if (!isset($options) && isset($display->display_options)) {
      $options = $display->display_options;
    }

    if ($this->is_default_display() && isset($options['defaults'])) {
      unset($options['defaults']);
    }

    // Cache for unpack_options, but not if we are in the ui.
    static $unpack_options = array();
    if (empty($view->editing)) {
      $cid = 'unpack_options:' . md5(serialize(array($this->options, $options)));
      if (empty($unpack_options[$cid])) {
        $cache = views_cache_get($cid, TRUE);
        if (!empty($cache->data)) {
          $this->options = $cache->data;
        }
        else {
          $this->unpack_options($this->options, $options);
          views_cache_set($cid, $this->options, TRUE);
        }
        $unpack_options[$cid] = $this->options;
      }
      else {
        $this->options = $unpack_options[$cid];
      }
    }
    else {
      $this->unpack_options($this->options, $options);
    }

    // Translate changed settings:
    $items_per_page = $this->get_option('items_per_page');
    $offset = $this->get_option('offset');
    $use_pager = $this->get_option('use_pager');
    $pager = $this->get_option('pager');
    // Check if the pager options were already converted.
    // The pager settings of a Views 2.x view specifying 10 items with an
    // offset of 0 and no pager is the same as of a Views 3.x view with
    // default settings. In this case, the only way to determine which case we
    // are dealing with is checking the API version but that's only available
    // for exported Views as it's not stored in the database.
    // If you would like to change this code, really take care that you thought
    // of every possibility.
    // @TODO: Provide a way to convert the database views as well.
    if (((!empty($items_per_page) && $items_per_page != 10) || !empty($offset) || !empty($use_pager))
      || (!empty($view->api_version) && $view->api_version == 2)) {
      // Find out the right pager type.
      // If the view "use pager" it's a normal/full pager.
      if ($use_pager) {
        $type = 'full';
      }
      // If it does not use pager, but 0 items per page it should not page
      // else it should display just a certain amount of items.
      else {
        $type = $items_per_page ? 'some' : 'none';
      }

      // Setup the pager options.
      $pager = array(
        'type' => $type,
        'options' => array(
          'offset' => intval($offset)
        ),
      );

      if ($items_per_page) {
        $pager['options']['items_per_page'] = $items_per_page;
      }
      // Setup the pager element.
      if ($id = $this->get_option('pager_element')) {
        $pager['options']['id'] = $id;
      }

      // Unset the previous options
      // After edit and save the view they will be erased
      $this->set_option('items_per_page', NULL);
      $this->set_option('offset', NULL);
      $this->set_option('use_pager', NULL);
      $this->set_option('pager', $pager);
      $changed = TRUE;
    }


    // Plugable headers, footer and empty texts are
    // not compatible with previous version of views
    // This code converts old values into a configured handler for each area
    foreach (array('header', 'footer', 'empty') as $area) {
      $converted = FALSE;
      if (isset($this->options[$area]) && !is_array($this->options[$area])) {
        if (!empty($this->options[$area])) {
          $content = $this->get_option($area);
          if (!empty($content) && !is_array($content)) {
            $format = $this->get_option($area . '_format');
            $options = array(
              'id' => 'area',
              'table' => 'views',
              'field' => 'area',
              'label' => '',
              'relationship' => 'none',
              'group_type' => 'group',
              'content' => $content,
              'format' => !empty($format) ? $format : filter_default_format(),
            );

            if ($area != 'empty' && $empty = $this->get_option($area . '_empty')) {
              $options['empty'] = $empty;
            }
            $this->set_option($area, array('text' => $options));
            $converted = TRUE;
            $changed = TRUE;
          }
        }
        // Ensure that options are at least an empty array
        if (!$converted) {
          $this->set_option($area, array());
        }
      }
    }

    // Convert distinct setting from display to query settings.
    $distinct = $this->get_option('distinct');
    if (!empty($distinct)) {
      $query_settings = $this->get_option('query');
      $query_settings['options']['distinct'] = $distinct;
      $this->set_option('query', $query_settings);
      // Clear the values
      $this->set_option('distinct', NULL);
      $changed = TRUE;
    }

    // Convert field language settings.
    $query_options = $this->get_option('query');
    if (isset($query_options['options']['field_language'])) {
      $this->set_option('field_language', $query_options['options']['field_language']);
      unset($query_options['options']['field_language']);
      $changed = TRUE;
    }
    if (isset($query_options['options']['field_language_add_to_query'])) {
      $this->set_option('field_language_add_to_query', $query_options['options']['field_language_add_to_query']);
      unset($query_options['options']['field_language_add_to_query']);
      $changed = TRUE;
    }
    $this->set_option('query', $query_options);

    // Convert filter groups.
    $filter_groups = $this->get_option('filter_groups');
    // Only convert if it wasn't converted yet, which is the case if there is a 0 group.
    if (isset($filter_groups['groups'][0])) {
      // Update filter groups.
      $filter_groups ['groups'] = views_array_key_plus($filter_groups['groups']);
      $this->set_option('filter_groups', $filter_groups);
      // Update the filter group on each filter.
      $filters = $this->get_option('filters');
      foreach ($filters as &$filter) {
        if (isset($filter['group'])) {
          $filter['group']++;
        }
        else {
          $filter['group'] = 1;
        }
      }
      $this->set_option('filters', $filters);
      $changed = TRUE;
    }

    // Filter groups were allowed to be rewritten without its filters, so
    // before this update the view was using the default values. To be sure that
    // the existing view isn't broken, don't use this overridden values but copy
    // them from the default display. Only do this if the filters are overridden
    // but the filter_groups are not marked as so.
    if (!$this->is_default_display() && !$this->options['defaults']['filters'] && $this->options['defaults']['filter_groups']) {
      // Set filter_groups to be overridden and save the value in the
      // display_options as well.
      $this->options['defaults']['filter_groups'] = FALSE;
      $this->display->display_options['defaults']['filter_groups'] = $this->options['defaults']['filter_groups'];
      // Copy the filter_groups from the default, and add them to the
      // display_options as well. $this->default_display is not initialized at
      // this point.
      $this->options['filter_groups'] = $this->view->display['default']->handler->options['filter_groups'];
      $this->display->display_options['filter_groups'] = $this->options['filter_groups'];

      $changed = TRUE;
    }

    // Mark the view as changed so the user has a chance to save it.
    if ($changed) {
      $this->view->changed = TRUE;
    }
  }

  function destroy() {
    parent::destroy();

    foreach ($this->handlers as $type => $handlers) {
      foreach ($handlers as $id => $handler) {
        if (is_object($handler)) {
          $this->handlers[$type][$id]->destroy();
        }
      }
    }

    if (isset($this->default_display)) {
      unset($this->default_display);
    }

    foreach ($this->extender as $extender) {
      $extender->destroy();
    }
  }

  /**
   * Determine if this display is the 'default' display which contains
   * fallback settings
   */
  function is_default_display() { return FALSE; }

  /**
   * Determine if this display uses exposed filters, so the view
   * will know whether or not to build them.
   */
  function uses_exposed() {
    if (!isset($this->has_exposed)) {
      foreach ($this->handlers as $type => $value) {
        foreach ($this->view->$type as $id => $handler) {
          if ($handler->can_expose() && $handler->is_exposed()) {
            // one is all we need; if we find it, return true.
            $this->has_exposed = TRUE;
            return TRUE;
          }
        }
      }
      $pager = $this->get_plugin('pager');
      if (isset($pager) && $pager->uses_exposed()) {
        $this->has_exposed = TRUE;
        return TRUE;
      }
      $this->has_exposed = FALSE;
    }

    return $this->has_exposed;
  }

  /**
   * Determine if this display should display the exposed
   * filters widgets, so the view will know whether or not
   * to render them.
   *
   * Regardless of what this function
   * returns, exposed filters will not be used nor
   * displayed unless uses_exposed() returns TRUE.
   */
  function displays_exposed() {
    return TRUE;
  }

  /**
   * Does the display use AJAX?
   */
  function use_ajax() {
    if (!empty($this->definition['use ajax'])) {
      return $this->get_option('use_ajax');
    }
    return FALSE;
  }

  /**
   * Does the display have a pager enabled?
   */
  function use_pager() {
    $pager = $this->get_plugin('pager');
    if ($pager) {
      return $pager->use_pager();
    }
  }

  /**
   * Does the display have a more link enabled?
   */
  function use_more() {
    if (!empty($this->definition['use more'])) {
      return $this->get_option('use_more');
    }
    return FALSE;
  }

  /**
   * Does the display have groupby enabled?
   */
  function use_group_by() {
    return $this->get_option('group_by');
  }

  /**
   * Should the enabled display more link be shown when no more items?
   */
  function use_more_always() {
    if (!empty($this->definition['use more'])) {
      return $this->get_option('use_more_always');
    }
    return FALSE;
  }

  /**
   * Does the display have custom link text?
   */
  function use_more_text() {
    if (!empty($this->definition['use more'])) {
      return $this->get_option('use_more_text');
    }
    return FALSE;
  }

  /**
   * Can this display accept attachments?
   */
  function accept_attachments() {
    if (empty($this->definition['accept attachments'])) {
      return FALSE;
    }
    if (!empty($this->view->argument) && $this->get_option('hide_attachment_summary')) {
      foreach ($this->view->argument as $argument_id => $argument) {
        if ($argument->needs_style_plugin() && empty($argument->argument_validated)) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Allow displays to attach to other views.
   */
  function attach_to($display_id) { }

  /**
   * Static member function to list which sections are defaultable
   * and what items each section contains.
   */
  function defaultable_sections($section = NULL) {
    $sections = array(
      'access' => array('access', 'access_options'),
      'access_options' => array('access', 'access_options'),
      'cache' => array('cache', 'cache_options'),
      'cache_options' => array('cache', 'cache_options'),
      'title' => array('title'),
      'css_class' => array('css_class'),
      'use_ajax' => array('use_ajax'),
      'hide_attachment_summary' => array('hide_attachment_summary'),
      'group_by' => array('group_by'),
      'query' => array('query'),
      'use_more' => array('use_more', 'use_more_always', 'use_more_text'),
      'link_display' => array('link_display', 'link_url'),

      // Force these to cascade properly.
      'style_plugin' => array('style_plugin', 'style_options', 'row_plugin', 'row_options'),
      'style_options' => array('style_plugin', 'style_options', 'row_plugin', 'row_options'),
      'row_plugin' => array('style_plugin', 'style_options', 'row_plugin', 'row_options'),
      'row_options' => array('style_plugin', 'style_options', 'row_plugin', 'row_options'),

      'pager' => array('pager', 'pager_options'),
      'pager_options' => array('pager', 'pager_options'),

      'exposed_form' => array('exposed_form', 'exposed_form_options'),
      'exposed_form_options' => array('exposed_form', 'exposed_form_options'),

      // These guys are special
      'header' => array('header'),
      'footer' => array('footer'),
      'empty' => array('empty'),
      'relationships' => array('relationships'),
      'fields' => array('fields'),
      'sorts' => array('sorts'),
      'arguments' => array('arguments'),
      'filters' => array('filters', 'filter_groups'),
      'filter_groups' => array('filters', 'filter_groups'),
    );

    // If the display cannot use a pager, then we cannot default it.
    if (empty($this->definition['use pager'])) {
      unset($sections['pager']);
      unset($sections['items_per_page']);
    }

    foreach ($this->extender as $extender) {
      $extender->defaultable_sections($sections, $section);
    }

    if ($section) {
      if (!empty($sections[$section])) {
        return $sections[$section];
      }
    }
    else {
      return $sections;
    }
  }

  function option_definition() {
    $options = array(
      'defaults' => array(
        'default' => array(
          'access' => TRUE,
          'cache' => TRUE,
          'query' => TRUE,
          'title' => TRUE,
          'css_class' => TRUE,

          'display_description' => FALSE,
          'use_ajax' => TRUE,
          'hide_attachment_summary' => TRUE,
          'pager' => TRUE,
          'pager_options' => TRUE,
          'use_more' => TRUE,
          'use_more_always' => TRUE,
          'use_more_text' => TRUE,
          'exposed_form' => TRUE,
          'exposed_form_options' => TRUE,

          'link_display' => TRUE,
          'link_url' => '',
          'group_by' => TRUE,

          'style_plugin' => TRUE,
          'style_options' => TRUE,
          'row_plugin' => TRUE,
          'row_options' => TRUE,

          'header' => TRUE,
          'footer' => TRUE,
          'empty' => TRUE,

          'relationships' => TRUE,
          'fields' => TRUE,
          'sorts' => TRUE,
          'arguments' => TRUE,
          'filters' => TRUE,
          'filter_groups' => TRUE,
        ),
        'export' => FALSE,
      ),

      'title' => array(
        'default' => '',
        'translatable' => TRUE,
      ),
      'enabled' => array(
        'default' => TRUE,
        'translatable' => FALSE,
        'bool' => TRUE,
      ),
      'display_comment' => array(
        'default' => '',
      ),
      'css_class' => array(
        'default' => '',
        'translatable' => FALSE,
      ),
      'display_description' => array(
        'default' => '',
        'translatable' => TRUE,
      ),
      'use_ajax' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'hide_attachment_summary' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      // This is legacy code:
      // Items_per/offset/use_pager is moved to the pager plugin
      // but the automatic update path needs this items defined, so don't remove it.
      // @see views_plugin_display::init()
      'items_per_page' => array(
        'default' => 10,
      ),
      'offset' => array(
        'default' => 0,
      ),
      'use_pager' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'use_more' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'use_more_always' => array(
        'default' => FALSE,
        'bool' => FALSE,
      ),
      'use_more_text' => array(
        'default' => 'more',
        'translatable' => TRUE,
      ),
      'link_display' => array(
        'default' => '',
      ),
      'link_url' => array(
        'default' => '',
      ),
      'group_by' => array(
        'default' => FALSE,
        'bool' => TRUE,
      ),
      'field_language' => array(
        'default' => '***CURRENT_LANGUAGE***',
      ),
      'field_language_add_to_query' => array(
        'default' => 1,
      ),

      // These types are all plugins that can have individual settings
      // and therefore need special handling.
      'access' => array(
        'contains' => array(
          'type' => array('default' => 'none', 'export' => 'export_plugin', 'unpack_translatable' => 'unpack_plugin'),
         ),
      ),
      'cache' => array(
        'contains' => array(
          'type' => array('default' => 'none', 'export' => 'export_plugin', 'unpack_translatable' => 'unpack_plugin'),
         ),
      ),
      'query' => array(
        'contains' => array(
          'type' => array('default' => 'views_query', 'export' => 'export_plugin'),
          'options' => array('default' => array(), 'export' => FALSE),
         ),
      ),
      // Note that exposed_form plugin has options in a separate array,
      // while access and cache do not. access and cache are legacy and
      // that pattern should not be repeated, but it is left as is to
      // reduce the need to modify older views. Let's consider the
      // pattern used here to be the template from which future plugins
      // should be copied.
      'exposed_form' => array(
        'contains' => array(
          'type' => array('default' => 'basic', 'export' => 'export_plugin', 'unpack_translatable' => 'unpack_plugin'),
          'options' => array('default' => array(), 'export' => FALSE),
         ),
      ),
      'pager' => array(
        'contains' => array(
          'type' => array('default' => 'full', 'export' => 'export_plugin', 'unpack_translatable' => 'unpack_plugin'),
          'options' => array('default' => array(), 'export' => FALSE),
         ),
      ),

      // Note that the styles have their options completely independent.
      // Like access and cache above, this is a legacy pattern and
      // should not be repeated.
      'style_plugin' => array(
        'default' => 'default',
        'export' => 'export_style',
        'unpack_translatable' => 'unpack_style',
      ),
      'style_options' => array(
        'default' => array(),
        'export' => FALSE,
      ),
      'row_plugin' => array(
        'default' => 'fields',
        'export' => 'export_style',
        'unpack_translatable' => 'unpack_style',
      ),
      'row_options' => array(
        'default' => array(),
        'export' => FALSE,
      ),

      'exposed_block' => array(
        'default' => FALSE,
      ),

      'header' => array(
        'default' => array(),
        'export' => 'export_handler',
        'unpack_translatable' => 'unpack_handler',
      ),
      'footer' => array(
        'default' => array(),
        'export' => 'export_handler',
        'unpack_translatable' => 'unpack_handler',
      ),
      'empty' => array(
        'default' => array(),
        'export' => 'export_handler',
        'unpack_translatable' => 'unpack_handler',
      ),

      // We want these to export last.
      // These are the 5 handler types.
      'relationships' => array(
        'default' => array(),
        'export' => 'export_handler',
        'unpack_translatable' => 'unpack_handler',

      ),
      'fields' => array(
        'default' => array(),
        'export' => 'export_handler',
        'unpack_translatable' => 'unpack_handler',
      ),
      'sorts' => array(
        'default' => array(),
        'export' => 'export_handler',
        'unpack_translatable' => 'unpack_handler',
      ),
      'arguments' => array(
        'default' => array(),
        'export' => 'export_handler',
        'unpack_translatable' => 'unpack_handler',
      ),
      'filter_groups' => array(
        'contains' => array(
          'operator' => array('default' => 'AND'),
          'groups' => array('default' => array(1 => 'AND')),
        ),
      ),
      'filters' => array(
        'default' => array(),
        'export' => 'export_handler',
        'unpack_translatable' => 'unpack_handler',
      ),
    );

    if (empty($this->definition['use pager'])) {
      $options['defaults']['default']['use_pager'] = FALSE;
      $options['defaults']['default']['items_per_page'] = FALSE;
      $options['defaults']['default']['offset'] = FALSE;
      $options['defaults']['default']['pager'] = FALSE;
      $options['pager']['contains']['type']['default'] = 'some';
    }

    if ($this->is_default_display()) {
      unset($options['defaults']);
    }

    foreach ($this->extender as $extender) {
      $extender->options_definition_alter($options);
    }

    return $options;
  }

  /**
   * Check to see if the display has a 'path' field.
   *
   * This is a pure function and not just a setting on the definition
   * because some displays (such as a panel pane) may have a path based
   * upon configuration.
   *
   * By default, displays do not have a path.
   */
  function has_path() { return FALSE; }

  /**
   * Check to see if the display has some need to link to another display.
   *
   * For the most part, displays without a path will use a link display. However,
   * sometimes displays that have a path might also need to link to another display.
   * This is true for feeds.
   */
  function uses_link_display() { return !$this->has_path(); }

  /**
   * Check to see if the display can put the exposed formin a block.
   *
   * By default, displays that do not have a path cannot disconnect
   * the exposed form and put it in a block, because the form has no
   * place to go and Views really wants the forms to go to a specific
   * page.
   */
  function uses_exposed_form_in_block() { return $this->has_path(); }

  /**
   * Check to see which display to use when creating links within
   * a view using this display.
   */
  function get_link_display() {
    $display_id = $this->get_option('link_display');
    // If unknown, pick the first one.
    if (empty($display_id) || empty($this->view->display[$display_id])) {
      foreach ($this->view->display as $display_id => $display) {
        if (!empty($display->handler) && $display->handler->has_path()) {
          return $display_id;
        }
      }
    }
    else {
      return $display_id;
    }
    // fall-through returns NULL
  }

  /**
   * Return the base path to use for this display.
   *
   * This can be overridden for displays that do strange things
   * with the path.
   */
  function get_path() {
    if ($this->has_path()) {
      return $this->get_option('path');
    }

    $display_id = $this->get_link_display();
    if ($display_id && !empty($this->view->display[$display_id]) && is_object($this->view->display[$display_id]->handler)) {
      return $this->view->display[$display_id]->handler->get_path();
    }
  }

  function get_url() {
    return $this->view->get_url();
  }

  /**
   * Check to see if the display needs a breadcrumb
   *
   * By default, displays do not need breadcrumbs
   */
  function uses_breadcrumb() { return FALSE; }

  /**
   * Determine if a given option is set to use the default display or the
   * current display
   *
   * @return
   *   TRUE for the default display
   */
  function is_defaulted($option) {
    return !$this->is_default_display() && !empty($this->default_display) && !empty($this->options['defaults'][$option]);
  }

  /**
   * Intelligently get an option either from this display or from the
   * default display, if directed to do so.
   */
  function get_option($option) {
    if ($this->is_defaulted($option)) {
      return $this->default_display->get_option($option);
    }

    if (array_key_exists($option, $this->options)) {
      return $this->options[$option];
    }
  }

  /**
   * Determine if the display's style uses fields.
   */
  function uses_fields() {
    $plugin = $this->get_plugin();
    if ($plugin) {
      return $plugin->uses_fields();
    }
  }

  /**
   * Get the instance of a plugin, for example style or row.
   *
   * @param string $type
   *   The type of the plugin.
   * @param string $name
   *   The name of the plugin defined in hook_views_plugins.
   *
   * @return views_plugin|FALSE
   */
  function get_plugin($type = 'style', $name = NULL) {
    static $cache = array();
    if (!isset($cache[$type][$name])) {
      switch ($type) {
        case 'style':
        case 'row':
          $option_name = $type . '_plugin';
          $options = $this->get_option($type . '_options');
          if (!$name) {
            $name = $this->get_option($option_name);
          }

          break;
        case 'query':
          $views_data = views_fetch_data($this->view->base_table);
          $name = !empty($views_data['table']['base']['query class']) ? $views_data['table']['base']['query class'] : 'views_query';
        default:
          $option_name = $type;
          $options = $this->get_option($type);
          if (!$name) {
            $name = $options['type'];
          }

          // access & cache store their options as siblings with the
          // type; all others use an 'options' array.
          if ($type != 'access' && $type != 'cache') {
            $options = $options['options'];
          }
      }
      $plugin = views_get_plugin($type, $name);

      if (!$plugin) {
        return;
      }
      if ($type != 'query') {
        $plugin->init($this->view, $this->display, $options);
      }
      else {
        $display_id = $this->is_defaulted($option_name) ? $this->display->id : 'default';
        $plugin->localization_keys = array($display_id, $type);

        if (!isset($this->base_field)) {
          $views_data = views_fetch_data($this->view->base_table);
          $this->view->base_field = !empty($views_data['table']['base']['field']) ? $views_data['table']['base']['field'] : '';
        }
        $plugin->init($this->view->base_table, $this->view->base_field, $options);
      }
      $cache[$type][$name] = $plugin;
    }

    return $cache[$type][$name];
  }

  /**
   * Get the handler object for a single handler.
   */
  function &get_handler($type, $id) {
    if (!isset($this->handlers[$type])) {
      $this->get_handlers($type);
    }

    if (isset($this->handlers[$type][$id])) {
      return $this->handlers[$type][$id];
    }

    // So we can return a reference.
    $null = NULL;
    return $null;
  }

  /**
   * Get a full array of handlers for $type. This caches them.
   */
  function get_handlers($type) {
    if (!isset($this->handlers[$type])) {
      $this->handlers[$type] = array();
      $types = views_object_types();
      $plural = $types[$type]['plural'];

      foreach ($this->get_option($plural) as $id => $info) {
        // If this is during form submission and there are temporary options
        // which can only appear if the view is in the edit cache, use those
        // options instead. This is used for AJAX multi-step stuff.
        if (isset($_POST['form_id']) && isset($this->view->temporary_options[$type][$id])) {
          $info = $this->view->temporary_options[$type][$id];
        }

        if ($info['id'] != $id) {
          $info['id'] = $id;
        }

        // If aggregation is on, the group type might override the actual
        // handler that is in use. This piece of code checks that and,
        // if necessary, sets the override handler.
        $override = NULL;
        if ($this->use_group_by() && !empty($info['group_type'])) {
          if (empty($this->view->query)) {
            $this->view->init_query();
          }
          $aggregate = $this->view->query->get_aggregation_info();
          if (!empty($aggregate[$info['group_type']]['handler'][$type])) {
            $override = $aggregate[$info['group_type']]['handler'][$type];
          }
        }

        if (!empty($types[$type]['type'])) {
          $handler_type = $types[$type]['type'];
        }
        else {
          $handler_type = $type;
        }

        $handler = views_get_handler($info['table'], $info['field'], $handler_type, $override);
        if ($handler) {
          // Special override for area types so they know where they come from.
          if ($handler_type == 'area') {
            $handler->handler_type = $type;
          }

          $handler->init($this->view, $info);
          $this->handlers[$type][$id] = &$handler;
        }

        // Prevent reference problems.
        unset($handler);
      }
    }

    return $this->handlers[$type];
  }

  /**
   * Retrieve a list of fields for the current display with the
   *  relationship associated if it exists.
   */
  function get_field_labels() {
    $options = array();
    foreach ($this->get_handlers('relationship') as $relationship => $handler) {
      if ($label = $handler->label()) {
        $relationships[$relationship] = $label;
      }
      else {
        $relationships[$relationship] = $handler->ui_name();
      }
    }

    foreach ($this->get_handlers('field') as $id => $handler) {
      if ($label = $handler->label()) {
        $options[$id] = $label;
      }
      else {
        $options[$id] = $handler->ui_name();
      }
      if (!empty($handler->options['relationship']) && !empty($relationships[$handler->options['relationship']])) {
        $options[$id] = '(' . $relationships[$handler->options['relationship']] . ') ' . $options[$id];
      }
    }
    return $options;
  }

  /**
   * Intelligently set an option either from this display or from the
   * default display, if directed to do so.
   */
  function set_option($option, $value) {
    if ($this->is_defaulted($option)) {
      return $this->default_display->set_option($option, $value);
    }

    // Set this in two places: On the handler where we'll notice it
    // but also on the display object so it gets saved. This should
    // only be a temporary fix.
    $this->display->display_options[$option] = $value;
    return $this->options[$option] = $value;
  }

  /**
   * Set an option and force it to be an override.
   */
  function override_option($option, $value) {
    $this->set_override($option, FALSE);
    $this->set_option($option, $value);
  }

  /**
   * Because forms may be split up into sections, this provides
   * an easy URL to exactly the right section. Don't override this.
   */
  function option_link($text, $section, $class = '', $title = '') {
    views_add_js('ajax');
    if (!empty($class)) {
      $text = '<span>' . $text . '</span>';
    }

    if (!trim($text)) {
      $text = t('Broken field');
    }

    if (empty($title)) {
      $title = $text;
    }

    return l($text, 'admin/structure/views/nojs/display/' . $this->view->name . '/' . $this->display->id . '/' . $section, array('attributes' => array('class' => 'views-ajax-link ' . $class, 'title' => $title, 'id' => drupal_html_id('views-' . $this->display->id . '-' . $section)), 'html' => TRUE));
  }

  /**
   * Returns to tokens for arguments.
   *
   * This function is similar to views_handler_field::get_render_tokens()
   * but without fields tokens.
   */
  function get_arguments_tokens() {
    $tokens = array();
    if (!empty($this->view->build_info['substitutions'])) {
      $tokens = $this->view->build_info['substitutions'];
    }
    $count = 0;
    foreach ($this->view->display_handler->get_handlers('argument') as $arg => $handler) {
      $token = '%' . ++$count;
      if (!isset($tokens[$token])) {
        $tokens[$token] = '';
      }

      // Use strip tags as there should never be HTML in the path.
      // However, we need to preserve special characters like " that
      // were removed by check_plain().
      $tokens['!' . $count] = isset($this->view->args[$count - 1]) ? strip_tags(decode_entities($this->view->args[$count - 1])) : '';
    }

    return $tokens;
  }

  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  function options_summary(&$categories, &$options) {
    $categories = array(
      'title' => array(
        'title' => t('Title'),
        'column' => 'first',
      ),
      'format' => array(
        'title' => t('Format'),
        'column' => 'first',
      ),
      'filters' => array(
        'title' => t('Filters'),
        'column' => 'first',
      ),
      'fields' => array(
        'title' => t('Fields'),
        'column' => 'first',
      ),
      'pager' => array(
        'title' => t('Pager'),
        'column' => 'second',
      ),
      'exposed' => array(
        'title' => t('Exposed form'),
        'column' => 'third',
        'build' => array(
          '#weight' => 1,
        ),
      ),
      'access' => array(
        'title' => '',
        'column' => 'second',
        'build' => array(
          '#weight' => -5,
        ),
      ),
      'other' => array(
        'title' => t('Other'),
        'column' => 'third',
        'build' => array(
          '#weight' => 2,
        ),
      ),
    );

    if ($this->display->id != 'default') {
      $options['display_id'] = array(
        'category' => 'other',
        'title' => t('Machine Name'),
        'value' => !empty($this->display->new_id) ? check_plain($this->display->new_id) : check_plain($this->display->id),
        'desc' => t('Change the machine name of this display.'),
      );
    }

    $display_comment = check_plain(drupal_substr($this->get_option('display_comment'), 0, 10));
    $options['display_comment'] = array(
      'category' => 'other',
      'title' => t('Comment'),
      'value' => !empty($display_comment) ? $display_comment : t('No comment'),
      'desc' => t('Comment or document this display.'),
    );

    $title = strip_tags($this->get_option('title'));
    if (!$title) {
      $title = t('None');
    }

    $options['title'] = array(
      'category' => 'title',
      'title' => t('Title'),
      'value' => $title,
      'desc' => t('Change the title that this display will use.'),
    );

    $style_plugin = views_fetch_plugin_data('style', $this->get_option('style_plugin'));
    $style_plugin_instance = $this->get_plugin('style');
    $style_summary = empty($style_plugin['title']) ? t('Missing style plugin') : $style_plugin_instance->summary_title();
    $style_title = empty($style_plugin['title']) ? t('Missing style plugin') : $style_plugin_instance->plugin_title();

    $style = '';

    $options['style_plugin'] = array(
      'category' => 'format',
      'title' => t('Format'),
      'value' => $style_title,
      'setting' => $style_summary,
      'desc' => t('Change the way content is formatted.'),
    );

    // This adds a 'Settings' link to the style_options setting if the style has options.
    if (!empty($style_plugin['uses options'])) {
      $options['style_plugin']['links']['style_options'] = t('Change settings for this format');
    }

    if (!empty($style_plugin['uses row plugin'])) {
      $row_plugin = views_fetch_plugin_data('row', $this->get_option('row_plugin'));
      $row_plugin_instance = $this->get_plugin('row');
      $row_summary = empty($row_plugin['title']) ? t('Missing style plugin') : $row_plugin_instance->summary_title();
      $row_title = empty($row_plugin['title']) ? t('Missing style plugin') : $row_plugin_instance->plugin_title();

      $options['row_plugin'] = array(
        'category' => 'format',
        'title' => t('Show'),
        'value' => $row_title,
        'setting' => $row_summary,
        'desc' => t('Change the way each row in the view is styled.'),
      );
      // This adds a 'Settings' link to the row_options setting if the row style has options.
      if (!empty($row_plugin['uses options'])) {
        $options['row_plugin']['links']['row_options'] = t('Change settings for this style');
      }
    }
    if (!empty($this->definition['use ajax'])) {
      $options['use_ajax'] = array(
        'category' => 'other',
        'title' => t('Use AJAX'),
        'value' => $this->get_option('use_ajax') ? t('Yes') : t('No'),
        'desc' => t('Change whether or not this display will use AJAX.'),
      );
    }
    if (!empty($this->definition['accept attachments'])) {
      $options['hide_attachment_summary'] = array(
        'category' => 'other',
        'title' => t('Hide attachments in summary'),
        'value' => $this->get_option('hide_attachment_summary') ? t('Yes') : t('No'),
        'desc' => t('Change whether or not to display attachments when displaying a contextual filter summary.'),
      );
    }

    $pager_plugin = $this->get_plugin('pager');
    if (!$pager_plugin) {
      // default to the no pager plugin.
      $pager_plugin = views_get_plugin('pager', 'none');
    }

    $pager_str = $pager_plugin->summary_title();

    $options['pager'] = array(
      'category' => 'pager',
      'title' => t('Use pager'),
      'value' => $pager_plugin->plugin_title(),
      'setting' => $pager_str,
      'desc' => t("Change this display's pager setting."),
    );

    // If pagers aren't allowed, change the text of the item:
    if (empty($this->definition['use pager'])) {
      $options['pager']['title'] = t('Items to display');
    }

    if (!empty($pager_plugin->definition['uses options'])) {
      $options['pager']['links']['pager_options'] = t('Change settings for this pager type.');
    }

    if (!empty($this->definition['use more'])) {
      $options['use_more'] = array(
        'category' => 'pager',
        'title' => t('More link'),
        'value' => $this->get_option('use_more') ? t('Yes') : t('No'),
        'desc' => t('Specify whether this display will provide a "more" link.'),
      );
    }

    $this->view->init_query();
    if ($this->view->query->get_aggregation_info()) {
      $options['group_by'] = array(
        'category' => 'other',
        'title' => t('Use aggregation'),
        'value' => $this->get_option('group_by') ? t('Yes') : t('No'),
        'desc' => t('Allow grouping and aggregation (calculation) of fields.'),
      );
    }

    $options['query'] = array(
      'category' => 'other',
      'title' => t('Query settings'),
      'value' => t('Settings'),
      'desc' => t('Allow to set some advanced settings for the query plugin'),
    );

    $languages = array(
        '***CURRENT_LANGUAGE***' => t("Current user's language"),
        '***DEFAULT_LANGUAGE***' => t("Default site language"),
        LANGUAGE_NONE => t('Language neutral'),
    );
    if (module_exists('locale')) {
      $languages = array_merge($languages, locale_language_list());
    }
    $field_language = array();
    $options['field_language'] = array(
      'category' => 'other',
      'title' => t('Field Language'),
      'value' => $languages[$this->get_option('field_language')],
      'desc' => t('All fields which support translations will be displayed in the selected language.'),
    );

    $access_plugin = $this->get_plugin('access');
    if (!$access_plugin) {
      // default to the no access control plugin.
      $access_plugin = views_get_plugin('access', 'none');
    }

    $access_str = $access_plugin->summary_title();

    $options['access'] = array(
      'category' => 'access',
      'title' => t('Access'),
      'value' => $access_plugin->plugin_title(),
      'setting' => $access_str,
      'desc' => t('Specify access control type for this display.'),
    );

    if (!empty($access_plugin->definition['uses options'])) {
      $options['access']['links']['access_options'] = t('Change settings for this access type.');
    }

    $cache_plugin = $this->get_plugin('cache');
    if (!$cache_plugin) {
      // default to the no cache control plugin.
      $cache_plugin = views_get_plugin('cache', 'none');
    }

    $cache_str = $cache_plugin->summary_title();

    $options['cache'] = array(
      'category' => 'other',
      'title' => t('Caching'),
      'value' => $cache_plugin->plugin_title(),
      'setting' => $cache_str,
      'desc' => t('Specify caching type for this display.'),
    );

    if (!empty($cache_plugin->definition['uses options'])) {
      $options['cache']['links']['cache_options'] = t('Change settings for this caching type.');
    }

    if (!empty($access_plugin->definition['uses options'])) {
      $options['access']['links']['access_options'] = t('Change settings for this access type.');
    }

    if ($this->uses_link_display()) {
      $display_id = $this->get_link_display();
      $link_display = empty($this->view->display[$display_id]) ? t('None') : check_plain($this->view->display[$display_id]->display_title);
      $link_display =  $this->get_option('link_display') == 'custom_url' ? t('Custom URL') : $link_display;
      $options['link_display'] = array(
        'category' => 'other',
        'title' => t('Link display'),
        'value' => $link_display,
        'desc' => t('Specify which display or custom url this display will link to.'),
      );
    }

    if ($this->uses_exposed_form_in_block()) {
      $options['exposed_block'] = array(
        'category' => 'exposed',
        'title' => t('Exposed form in block'),
        'value' => $this->get_option('exposed_block') ? t('Yes') : t('No'),
        'desc' => t('Allow the exposed form to appear in a block instead of the view.'),
      );
    }

    $exposed_form_plugin = $this->get_plugin('exposed_form');
    if (!$exposed_form_plugin) {
      // default to the no cache control plugin.
      $exposed_form_plugin = views_get_plugin('exposed_form', 'basic');
    }

    $exposed_form_str = $exposed_form_plugin->summary_title();

    $options['exposed_form'] = array(
      'category' => 'exposed',
      'title' => t('Exposed form style'),
      'value' => $exposed_form_plugin->plugin_title(),
      'setting' => $exposed_form_str,
      'desc' => t('Select the kind of exposed filter to use.'),
    );

    if (!empty($exposed_form_plugin->definition['uses options'])) {
      $options['exposed_form']['links']['exposed_form_options'] = t('Exposed form settings for this exposed form style.');
    }

    $css_class = check_plain(trim($this->get_option('css_class')));
    if (!$css_class) {
      $css_class = t('None');
    }

    $options['css_class'] = array(
      'category' => 'other',
      'title' => t('CSS class'),
      'value' => $css_class,
      'desc' => t('Change the CSS class name(s) that will be added to this display.'),
    );

    $options['analyze-theme'] = array(
      'category' => 'other',
      'title' => t('Theme'),
      'value' => t('Information'),
      'desc' => t('Get information on how to theme this display'),
    );

    foreach ($this->extender as $extender) {
      $extender->options_summary($categories, $options);
    }
  }

  /**
   * Provide the default form for setting options.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    if ($this->defaultable_sections($form_state['section'])) {
      views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
    }
    $form['#title'] = check_plain($this->display->display_title) . ': ';

    // Set the 'section' to hilite on the form.
    // If it's the item we're looking at is pulling from the default display,
    // reflect that. Don't use is_defaulted since we want it to show up even
    // on the default display.
    if (!empty($this->options['defaults'][$form_state['section']])) {
      $form['#section'] = 'default-' . $form_state['section'];
    }
    else {
      $form['#section'] = $this->display->id . '-' . $form_state['section'];
    }

    switch ($form_state['section']) {
      case 'display_id':
        $form['#title'] .= t('The machine name of this display');
        $form['display_id'] = array(
          '#type' => 'textfield',
          '#description' => t('This is machine name of the display.'),
          '#default_value' => !empty($this->display->new_id) ? $this->display->new_id : $this->display->id,
          '#required' => TRUE,
          '#size' => 64,
        );
        break;
      case 'display_title':
        $form['#title'] .= t('The name and the description of this display');
        $form['display_title'] = array(
          '#title' => t('Name'),
          '#type' => 'textfield',
          '#description' => t('This name will appear only in the administrative interface for the View.'),
          '#default_value' => $this->display->display_title,
        );
        $form['display_description'] = array(
          '#title' => t('Description'),
          '#type' => 'textfield',
          '#description' => t('This description will appear only in the administrative interface for the View.'),
          '#default_value' => $this->get_option('display_description'),
        );
        break;
      case 'display_comment':
        $form['#title'] .= t("This display's comments");
        $form['display_comment'] = array(
          '#type' => 'textarea',
          '#description' => t('This value will be seen and used only within the Views UI and can be used to document this display. You can use this to provide notes for other or future maintainers of your site about how or why this display is configured.'),
          '#default_value' => $this->get_option('display_comment'),
        );
        break;
      case 'title':
        $form['#title'] .= t('The title of this view');
        $form['title'] = array(
          '#type' => 'textfield',
          '#description' => t('This title will be displayed with the view, wherever titles are normally displayed; i.e, as the page title, block title, etc.'),
          '#default_value' => $this->get_option('title'),
        );
        break;
      case 'css_class':
        $form['#title'] .= t('CSS class');
        $form['css_class'] = array(
          '#type' => 'textfield',
          '#description' => t('The CSS class names will be added to the view. This enables you to use specific CSS code for each view. You may define multiples classes separated by spaces.'),
          '#default_value' => $this->get_option('css_class'),
        );
        break;
      case 'use_ajax':
        $form['#title'] .= t('Use AJAX when available to load this view');
        $form['description'] = array(
          '#markup' => '<div class="description form-item">' . t('If set, this view will use an AJAX mechanism for paging, table sorting and exposed filters. This means the entire page will not refresh. It is not recommended that you use this if this view is the main content of the page as it will prevent deep linking to specific pages, but it is very useful for side content.') . '</div>',
        );
        $form['use_ajax'] = array(
          '#type' => 'radios',
          '#options' => array(1 => t('Yes'), 0 => t('No')),
          '#default_value' => $this->get_option('use_ajax') ? 1 : 0,
        );
        break;
      case 'hide_attachment_summary':
        $form['#title'] .= t('Hide attachments when displaying a contextual filter summary');
        $form['hide_attachment_summary'] = array(
          '#type' => 'radios',
          '#options' => array(1 => t('Yes'), 0 => t('No')),
          '#default_value' => $this->get_option('hide_attachment_summary') ? 1 : 0,
        );
        break;
      case 'use_more':
        $form['#title'] .= t('Add a more link to the bottom of the display.');
        $form['use_more'] = array(
          '#type' => 'checkbox',
          '#title' => t('Create more link'),
          '#description' => t("This will add a more link to the bottom of this view, which will link to the page view. If you have more than one page view, the link will point to the display specified in 'Link display' above. You can override the url at the link display setting."),
          '#default_value' => $this->get_option('use_more'),
        );
        $form['use_more_always'] = array(
          '#type' => 'checkbox',
          '#title' => t("Display 'more' link only if there is more content"),
          '#description' => t("Leave this unchecked to display the 'more' link even if there are no more items to display."),
          '#default_value' => !$this->get_option('use_more_always'),
            '#dependency' => array(
              'edit-use-more' => array(TRUE),
            ),
        );
        $form['use_more_text'] = array(
          '#type' => 'textfield',
          '#title' => t('More link text'),
          '#description' => t("The text to display for the more link."),
          '#default_value' => $this->get_option('use_more_text'),
          '#dependency' => array(
            'edit-use-more' => array(TRUE),
          ),
        );
        break;
      case 'group_by':
        $form['#title'] .= t('Allow grouping and aggregation (calculation) of fields.');
        $form['group_by'] = array(
          '#type' => 'checkbox',
          '#title' => t('Aggregate'),
          '#description' => t('If enabled, some fields may become unavailable. All fields that are selected for grouping will be collapsed to one record per distinct value. Other fields which are selected for aggregation will have the function run on them. For example, you can group nodes on title and count the number of nids in order to get a list of duplicate titles.'),
          '#default_value' => $this->get_option('group_by'),
        );
        break;
      case 'access':
        $form['#title'] .= t('Access restrictions');
        $form['access'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $access = $this->get_option('access');
        $form['access']['type'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('access', NULL, array($this->view->base_table)),
          '#default_value' => $access['type'],
        );

        $access_plugin = views_fetch_plugin_data('access', $access['type']);
        if (!empty($access_plugin['uses options'])) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#markup' => t('You may also adjust the !settings for the currently selected access restriction.', array('!settings' => $this->option_link(t('settings'), 'access_options'))),
            '#suffix' => '</div>',
          );
        }

        break;
      case 'access_options':
        $access = $this->get_option('access');
        $plugin = $this->get_plugin('access');
        $form['#title'] .= t('Access options');
        if ($plugin) {
          $form['#help_topic'] = $plugin->definition['help topic'];
          $form['#help_module'] = $plugin->definition['module'];

          $form['access_options'] = array(
            '#tree' => TRUE,
          );
          $form['access_options']['type'] = array(
            '#type' => 'value',
            '#value' => $access['type'],
          );
          $plugin->options_form($form['access_options'], $form_state);
        }
        break;
      case 'cache':
        $form['#title'] .= t('Caching');
        $form['cache'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $cache = $this->get_option('cache');
        $form['cache']['type'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('cache', NULL, array($this->view->base_table)),
          '#default_value' => $cache['type'],
        );

        $cache_plugin = views_fetch_plugin_data('cache', $cache['type']);
        if (!empty($cache_plugin['uses options'])) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => t('You may also adjust the !settings for the currently selected cache mechanism.', array('!settings' => $this->option_link(t('settings'), 'cache_options'))),
          );
        }
        break;
      case 'cache_options':
        $cache = $this->get_option('cache');
        $plugin = $this->get_plugin('cache');
        $form['#title'] .= t('Caching options');
        if ($plugin) {
          $form['#help_topic'] = $plugin->definition['help topic'];
          $form['#help_module'] = $plugin->definition['module'];

          $form['cache_options'] = array(
            '#tree' => TRUE,
          );
          $form['cache_options']['type'] = array(
            '#type' => 'value',
            '#value' => $cache['type'],
          );
          $plugin->options_form($form['cache_options'], $form_state);
        }
        break;
      case 'query':
        $query_options = $this->get_option('query');
        $plugin_name = $query_options['type'];

        $form['#title'] .= t('Query options');
        $this->view->init_query();
        if ($this->view->query) {
          if (isset($this->view->query->definition['help topic'])) {
            $form['#help_topic'] = $this->view->query->definition['help topic'];
          }

          if (isset($this->view->query->definition['module'])) {
            $form['#help_module'] = $this->view->query->definition['module'];
          }

          $form['query'] = array(
            '#tree' => TRUE,
            'type' => array(
              '#type' => 'value',
              '#value' => $plugin_name,
            ),
            'options' => array(
              '#tree' => TRUE,
            ),
          );

          $this->view->query->options_form($form['query']['options'], $form_state);
        }
        break;
      case 'field_language':
        $form['#title'] .= t('Field Language');

        $entities = entity_get_info();
        $entity_tables = array();
        $has_translation_handlers = FALSE;
        foreach ($entities as $type => $entity_info) {
          $entity_tables[] = $entity_info['base table'];

          if (!empty($entity_info['translation'])) {
            $has_translation_handlers = TRUE;
          }
        }

        // Doesn't make sense to show a field setting here if we aren't querying
        // an entity base table. Also, we make sure that there's at least one
        // entity type with a translation handler attached.
        if (in_array($this->view->base_table, $entity_tables) && $has_translation_handlers) {
          $languages = array(
            '***CURRENT_LANGUAGE***' => t("Current user's language"),
            '***DEFAULT_LANGUAGE***' => t("Default site language"),
            LANGUAGE_NONE => t('Language neutral'),
          );
          $languages = array_merge($languages, views_language_list());

          $form['field_language'] = array(
            '#type' => 'select',
            '#title' => t('Field Language'),
            '#description' => t('All fields which support translations will be displayed in the selected language.'),
            '#options' => $languages,
            '#default_value' => $this->get_option('field_language'),
          );
          $form['field_language_add_to_query'] = array(
            '#type' => 'checkbox',
            '#title' => t('When needed, add the field language condition to the query'),
            '#default_value' => $this->get_option('field_language_add_to_query'),
          );
        }
        else {
          $form['field_language']['#markup'] = t("You don't have translatable entity types.");
        }
        break;
      case 'style_plugin':
        $form['#title'] .= t('How should this view be styled');
        $form['#help_topic'] = 'style';
        $form['style_plugin'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('style', $this->get_style_type(), array($this->view->base_table)),
          '#default_value' => $this->get_option('style_plugin'),
          '#description' => t('If the style you choose has settings, be sure to click the settings button that will appear next to it in the View summary.'),
        );

        $style_plugin = views_fetch_plugin_data('style', $this->get_option('style_plugin'));
        if (!empty($style_plugin['uses options'])) {
          $form['markup'] = array(
            '#markup' => '<div class="form-item description">' . t('You may also adjust the !settings for the currently selected style.', array('!settings' => $this->option_link(t('settings'), 'style_options'))) . '</div>',
          );
        }

        break;
      case 'style_options':
        $form['#title'] .= t('Style options');
        $style = TRUE;
        $type = 'style_plugin';
        $name = $this->get_option('style_plugin');

      case 'row_options':
        if (!isset($name)) {
          $name = $this->get_option('row_plugin');
        }
        // if row, $style will be empty.
        if (empty($style)) {
          $form['#title'] .= t('Row style options');
          $type = 'row_plugin';
        }
        $plugin = $this->get_plugin(empty($style) ? 'row' : 'style');
        if ($plugin) {
          if (isset($plugin->definition['help topic'])) {
            $form['#help_topic'] = $plugin->definition['help topic'];
            $form['#help_module'] = $plugin->definition['module'];
          }
          $form[$form_state['section']] = array(
            '#tree' => TRUE,
          );
          $plugin->options_form($form[$form_state['section']], $form_state);
        }
        break;
      case 'row_plugin':
        $form['#title'] .= t('How should each row in this view be styled');
        $form['#help_topic'] = 'style-row';
        $form['row_plugin'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('row', $this->get_style_type(), array($this->view->base_table)),
          '#default_value' => $this->get_option('row_plugin'),
        );

        $row_plugin = views_fetch_plugin_data('row', $this->get_option('row_plugin'));
        if (!empty($row_plugin['uses options'])) {
          $form['markup'] = array(
            '#markup' => '<div class="form-item description">' . t('You may also adjust the !settings for the currently selected row style.', array('!settings' => $this->option_link(t('settings'), 'row_options'))) . '</div>',
          );
        }

        break;
      case 'link_display':
        $form['#title'] .= t('Which display to use for path');
        foreach ($this->view->display as $display_id => $display) {
          if ($display->handler->has_path()) {
            $options[$display_id] = $display->display_title;
          }
        }
        $options['custom_url'] = t('Custom URL');
        if (count($options)) {
          $form['link_display'] = array(
            '#type' => 'radios',
            '#options' => $options,
            '#description' => t("Which display to use to get this display's path for things like summary links, rss feed links, more links, etc."),
            '#default_value' => $this->get_option('link_display'),
          );
        }

        $options = array();
        $count = 0; // This lets us prepare the key as we want it printed.
        foreach ($this->view->display_handler->get_handlers('argument') as $arg => $handler) {
          $options[t('Arguments')]['%' . ++$count] = t('@argument title', array('@argument' => $handler->ui_name()));
          $options[t('Arguments')]['!' . $count] = t('@argument input', array('@argument' => $handler->ui_name()));
        }

        // Default text.
        // We have some options, so make a list.
        $output = '';
        if (!empty($options)) {
          $output = t('<p>The following tokens are available for this link.</p>');
          foreach (array_keys($options) as $type) {
            if (!empty($options[$type])) {
              $items = array();
              foreach ($options[$type] as $key => $value) {
                $items[] = $key . ' == ' . $value;
              }
              $output .= theme('item_list',
                array(
                  'items' => $items,
                  'type' => $type
                ));
            }
          }
        }

        $form['link_url'] = array(
          '#type' => 'textfield',
          '#title' => t('Custom URL'),
          '#default_value' => $this->get_option('link_url'),
          '#description' => t('A Drupal path or external URL the more link will point to. Note that this will override the link display setting above.') . $output,
          '#dependency' => array('radio:link_display' => array('custom_url')),
        );
        break;
      case 'analyze-theme':
        $form['#title'] .= t('Theming information');
        $form['#help_topic'] = 'analyze-theme';

        if (isset($_POST['theme'])) {
          $this->theme = $_POST['theme'];
        }
        elseif (empty($this->theme)) {
          $this->theme = variable_get('theme_default', 'bartik');
        }

        if (isset($GLOBALS['theme']) && $GLOBALS['theme'] == $this->theme) {
          $this->theme_registry = theme_get_registry();
          $theme_engine = $GLOBALS['theme_engine'];
        }
        else {
          $themes = list_themes();
          $theme = $themes[$this->theme];

          // Find all our ancestor themes and put them in an array.
          $base_theme = array();
          $ancestor = $this->theme;
          while ($ancestor && isset($themes[$ancestor]->base_theme)) {
            $ancestor = $themes[$ancestor]->base_theme;
            $base_theme[] = $themes[$ancestor];
          }

          // The base themes should be initialized in the right order.
          $base_theme = array_reverse($base_theme);

          // This code is copied directly from _drupal_theme_initialize()
          $theme_engine = NULL;

          // Initialize the theme.
          if (isset($theme->engine)) {
            // Include the engine.
            include_once DRUPAL_ROOT . '/' . $theme->owner;

            $theme_engine = $theme->engine;
            if (function_exists($theme_engine . '_init')) {
              foreach ($base_theme as $base) {
                call_user_func($theme_engine . '_init', $base);
              }
              call_user_func($theme_engine . '_init', $theme);
            }
          }
          else {
            // include non-engine theme files
            foreach ($base_theme as $base) {
              // Include the theme file or the engine.
              if (!empty($base->owner)) {
                include_once DRUPAL_ROOT . '/' . $base->owner;
              }
            }
            // and our theme gets one too.
            if (!empty($theme->owner)) {
              include_once DRUPAL_ROOT . '/' . $theme->owner;
            }
          }
          $this->theme_registry = _theme_load_registry($theme, $base_theme, $theme_engine);
        }

        // If there's a theme engine involved, we also need to know its extension
        // so we can give the proper filename.
        $this->theme_extension = '.tpl.php';
        if (isset($theme_engine)) {
          $extension_function = $theme_engine . '_extension';
          if (function_exists($extension_function)) {
            $this->theme_extension = $extension_function();
          }
        }

        $funcs = array();
        // Get theme functions for the display. Note that some displays may
        // not have themes. The 'feed' display, for example, completely
        // delegates to the style.
        if (!empty($this->definition['theme'])) {
          $funcs[] = $this->option_link(t('Display output'), 'analyze-theme-display') . ': '  . $this->format_themes($this->theme_functions());
          $themes = $this->additional_theme_functions();
          if ($themes) {
            foreach ($themes as $theme) {
              $funcs[] = $this->option_link(t('Alternative display output'), 'analyze-theme-display') . ': '  . $this->format_themes($theme);
            }
          }
        }

        $plugin = $this->get_plugin();
        if ($plugin) {
          $funcs[] = $this->option_link(t('Style output'), 'analyze-theme-style') . ': ' . $this->format_themes($plugin->theme_functions(), $plugin->additional_theme_functions());
          $themes = $plugin->additional_theme_functions();
          if ($themes) {
            foreach ($themes as $theme) {
              $funcs[] = $this->option_link(t('Alternative style'), 'analyze-theme-style') . ': '  . $this->format_themes($theme);
            }
          }

          if ($plugin->uses_row_plugin()) {
            $row_plugin = $this->get_plugin('row');
            if ($row_plugin) {
              $funcs[] = $this->option_link(t('Row style output'), 'analyze-theme-row') . ': ' . $this->format_themes($row_plugin->theme_functions());
              $themes = $row_plugin->additional_theme_functions();
              if ($themes) {
                foreach ($themes as $theme) {
                  $funcs[] = $this->option_link(t('Alternative row style'), 'analyze-theme-row') . ': '  . $this->format_themes($theme);
                }
              }
            }
          }

          if ($plugin->uses_fields()) {
            foreach ($this->get_handlers('field') as $id => $handler) {
              $funcs[] = $this->option_link(t('Field @field (ID: @id)', array('@field' => $handler->ui_name(), '@id' => $id)), 'analyze-theme-field') . ': ' . $this->format_themes($handler->theme_functions());
            }
          }
        }

        $form['important'] = array(
          '#markup' => '<div class="form-item description"><p>' . t('This section lists all possible templates for the display plugin and for the style plugins, ordered roughly from the least specific to the most specific. The active template for each plugin -- which is the most specific template found on the system -- is highlighted in bold.') . '</p></div>',
        );

        if (isset($this->view->display[$this->view->current_display]->new_id)) {
          $form['important']['new_id'] = array(
            '#prefix' => '<div class="description">',
            '#suffix' => '</div>',
            '#value' => t("<strong>Important!</strong> You have changed the display's machine name. Anything that attached to this display specifically, such as theming, may stop working until it is updated. To see theme suggestions for it, you need to save the view."),
          );
        }

        foreach (list_themes() as $key => $theme) {
          if (!empty($theme->info['hidden'])) {
            continue;
          }
          $options[$key] = $theme->info['name'];
        }

        $form['box'] = array(
          '#prefix' => '<div class="container-inline">',
          '#suffix' => '</div>',
        );
        $form['box']['theme'] = array(
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $this->theme,
        );

        $form['box']['change'] = array(
          '#type' => 'submit',
          '#value' => t('Change theme'),
          '#submit' => array('views_ui_edit_display_form_change_theme'),
        );

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . theme('item_list', array('items' => $funcs)) . '</div>',
        );

        $form['rescan_button'] = array(
          '#prefix' => '<div class="form-item">',
          '#suffix' => '</div>',
        );
        $form['rescan_button']['button'] = array(
          '#type' => 'submit',
          '#value' => t('Rescan template files'),
          '#submit' => array('views_ui_config_item_form_rescan'),
        );
        $form['rescan_button']['markup'] = array(
          '#markup' => '<div class="description">' . t("<strong>Important!</strong> When adding, removing, or renaming template files, it is necessary to make Drupal aware of the changes by making it rescan the files on your system. By clicking this button you clear Drupal's theme registry and thereby trigger this rescanning process. The highlighted templates above will then reflect the new state of your system.") . '</div>',
        );

        $form_state['ok_button'] = TRUE;
        break;
      case 'analyze-theme-display':
        $form['#title'] .= t('Theming information (display)');
        $output = '<p>' . t('Back to !info.', array('!info' => $this->option_link(t('theming information'), 'analyze-theme'))) . '</p>';

        if (empty($this->definition['theme'])) {
          $output .= t('This display has no theming information');
        }
        else {
          $output .= '<p>' . t('This is the default theme template used for this display.') . '</p>';
          $output .= '<pre>' . check_plain(file_get_contents('./' . $this->definition['theme path'] . '/' . strtr($this->definition['theme'], '_', '-') . '.tpl.php')) . '</pre>';
        }

        if (!empty($this->definition['additional themes'])) {
          foreach ($this->definition['additional themes'] as $theme => $type) {
            $output .= '<p>' . t('This is an alternative template for this display.') . '</p>';
            $output .= '<pre>' . check_plain(file_get_contents('./' . $this->definition['theme path'] . '/' . strtr($theme, '_', '-') . '.tpl.php')) . '</pre>';
          }
        }

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . $output . '</div>',
        );

        $form_state['ok_button'] = TRUE;
        break;
      case 'analyze-theme-style':
        $form['#title'] .= t('Theming information (style)');
        $output = '<p>' . t('Back to !info.', array('!info' => $this->option_link(t('theming information'), 'analyze-theme'))) . '</p>';

        $plugin = $this->get_plugin();

        if (empty($plugin->definition['theme'])) {
          $output .= t('This display has no style theming information');
        }
        else {
          $output .= '<p>' . t('This is the default theme template used for this style.') . '</p>';
          $output .= '<pre>' . check_plain(file_get_contents('./' . $plugin->definition['theme path'] . '/' . strtr($plugin->definition['theme'], '_', '-') . '.tpl.php')) . '</pre>';
        }

        if (!empty($plugin->definition['additional themes'])) {
          foreach ($plugin->definition['additional themes'] as $theme => $type) {
            $output .= '<p>' . t('This is an alternative template for this style.') . '</p>';
            $output .= '<pre>' . check_plain(file_get_contents('./' . $plugin->definition['theme path'] . '/' . strtr($theme, '_', '-') . '.tpl.php')) . '</pre>';
          }
        }

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . $output . '</div>',
        );

        $form_state['ok_button'] = TRUE;
        break;
      case 'analyze-theme-row':
        $form['#title'] .= t('Theming information (row style)');
        $output = '<p>' . t('Back to !info.', array('!info' => $this->option_link(t('theming information'), 'analyze-theme'))) . '</p>';

        $plugin = $this->get_plugin('row');

        if (empty($plugin->definition['theme'])) {
          $output .= t('This display has no row style theming information');
        }
        else {
          $output .= '<p>' . t('This is the default theme template used for this row style.') . '</p>';
          $output .= '<pre>' . check_plain(file_get_contents('./' . $plugin->definition['theme path'] . '/' . strtr($plugin->definition['theme'], '_', '-') . '.tpl.php')) . '</pre>';
        }

        if (!empty($plugin->definition['additional themes'])) {
          foreach ($plugin->definition['additional themes'] as $theme => $type) {
            $output .= '<p>' . t('This is an alternative template for this row style.') . '</p>';
            $output .= '<pre>' . check_plain(file_get_contents('./' . $plugin->definition['theme path'] . '/' . strtr($theme, '_', '-') . '.tpl.php')) . '</pre>';
          }
        }

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . $output . '</div>',
        );

        $form_state['ok_button'] = TRUE;
        break;
      case 'analyze-theme-field':
        $form['#title'] .= t('Theming information (row style)');
        $output = '<p>' . t('Back to !info.', array('!info' => $this->option_link(t('theming information'), 'analyze-theme'))) . '</p>';

        $output .= '<p>' . t('This is the default theme template used for this row style.') . '</p>';

        // Field templates aren't registered the normal way...and they're always
        // this one, anyhow.
        $output .= '<pre>' . check_plain(file_get_contents(drupal_get_path('module', 'views') . '/theme/views-view-field.tpl.php')) . '</pre>';

        $form['analysis'] = array(
          '#markup' => '<div class="form-item">' . $output . '</div>',
        );
        $form_state['ok_button'] = TRUE;
        break;

      case 'exposed_block':
        $form['#title'] .= t('Put the exposed form in a block');
        $form['description'] = array(
          '#markup' => '<div class="description form-item">' . t('If set, any exposed widgets will not appear with this view. Instead, a block will be made available to the Drupal block administration system, and the exposed form will appear there. Note that this block must be enabled manually, Views will not enable it for you.') . '</div>',
        );
        $form['exposed_block'] = array(
          '#type' => 'radios',
          '#options' => array(1 => t('Yes'), 0 => t('No')),
          '#default_value' => $this->get_option('exposed_block') ? 1 : 0,
        );
        break;
      case 'exposed_form':
        $form['#title'] .= t('Exposed Form');
        $form['exposed_form'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $exposed_form = $this->get_option('exposed_form');
        $form['exposed_form']['type'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('exposed_form', NULL, array($this->view->base_table)),
          '#default_value' => $exposed_form['type'],
        );

        $exposed_form_plugin = views_fetch_plugin_data('exposed_form', $exposed_form['type']);
        if (!empty($exposed_form_plugin['uses options'])) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => t('You may also adjust the !settings for the currently selected style.', array('!settings' => $this->option_link(t('settings'), 'exposed_form_options'))),
          );
        }
        break;
      case 'exposed_form_options':
        $plugin = $this->get_plugin('exposed_form');
        $form['#title'] .= t('Exposed form options');
        if ($plugin) {
          $form['#help_topic'] = $plugin->definition['help topic'];

          $form['exposed_form_options'] = array(
            '#tree' => TRUE,
          );
          $plugin->options_form($form['exposed_form_options'], $form_state);
        }
        break;
      case 'pager':
        $form['#title'] .= t('Select which pager, if any, to use for this view');
        $form['pager'] = array(
          '#prefix' => '<div class="clearfix">',
          '#suffix' => '</div>',
          '#tree' => TRUE,
        );

        $pager = $this->get_option('pager');
        $form['pager']['type'] =  array(
          '#type' => 'radios',
          '#options' => views_fetch_plugin_names('pager', empty($this->definition['use pager']) ? 'basic' : NULL, array($this->view->base_table)),
          '#default_value' => $pager['type'],
        );

        $pager_plugin = views_fetch_plugin_data('pager', $pager['type'], array($this->view->base_table));
        if (!empty($pager_plugin['uses options'])) {
          $form['markup'] = array(
            '#prefix' => '<div class="form-item description">',
            '#suffix' => '</div>',
            '#markup' => t('You may also adjust the !settings for the currently selected pager.', array('!settings' => $this->option_link(t('settings'), 'pager_options'))),
          );
        }

        break;
      case 'pager_options':
        $plugin = $this->get_plugin('pager');
        $form['#title'] .= t('Pager options');
        if ($plugin) {
          $form['#help_topic'] = $plugin->definition['help topic'];

          $form['pager_options'] = array(
            '#tree' => TRUE,
          );
          $plugin->options_form($form['pager_options'], $form_state);
        }
        break;
    }

    foreach ($this->extender as $extender) {
      $extender->options_form($form, $form_state);
    }
  }

  /**
   * Format a list of theme templates for output by the theme info helper.
   */
  function format_themes($themes) {
    $registry = $this->theme_registry;
    $extension = $this->theme_extension;

    $output = '';
    $picked = FALSE;
    foreach ($themes as $theme) {
      $template = strtr($theme, '_', '-') . $extension;
      if (!$picked && !empty($registry[$theme])) {
        $template_path = isset($registry[$theme]['path']) ? $registry[$theme]['path'] . '/' : './';
        if (file_exists($template_path . $template)) {
          $hint = t('File found in folder @template-path', array('@template-path' => $template_path));
          $template = '<strong title="'. $hint .'">' . $template . '</strong>';
        }
        else {
          $template = '<strong class="error">' . $template . ' ' . t('(File not found, in folder @template-path)', array('@template-path' => $template_path)) . '</strong>';
        }
        $picked = TRUE;
      }
      $fixed[] = $template;
    }

    return implode(', ', array_reverse($fixed));
  }

  /**
   * Validate the options form.
   */
  function options_validate(&$form, &$form_state) {
    switch ($form_state['section']) {
      case 'display_title':
        if (empty($form_state['values']['display_title'])) {
          form_error($form['display_title'], t('Display title may not be empty.'));
        }
        break;
      case 'css_class':
        $css_class = $form_state['values']['css_class'];
        if (preg_match('/[^a-zA-Z0-9-_ ]/', $css_class)) {
          form_error($form['css_class'], t('CSS classes must be alphanumeric or dashes only.'));
        }
      break;
      case 'display_id':
        if ($form_state['values']['display_id']) {
          if (preg_match('/[^a-z0-9_]/', $form_state['values']['display_id'])) {
            form_error($form['display_id'], t('Display name must be letters, numbers, or underscores only.'));
          }

          foreach ($this->view->display as $id => $display) {
            if ($id != $this->view->current_display && ($form_state['values']['display_id'] == $id || (isset($display->new_id) && $form_state['values']['display_id'] == $display->new_id))) {
              form_error($form['display_id'], t('Display id should be unique.'));
            }
          }
        }
        break;
      case 'style_options':
        $style = TRUE;
      case 'row_options':
        // if row, $style will be empty.
        $plugin = $this->get_plugin(empty($style) ? 'row' : 'style');
        if ($plugin) {
          $plugin->options_validate($form[$form_state['section']], $form_state);
        }
        break;
      case 'access_options':
        $plugin = $this->get_plugin('access');
        if ($plugin) {
          $plugin->options_validate($form['access_options'], $form_state);
        }
        break;
      case 'query':
        if ($this->view->query) {
          $this->view->query->options_validate($form['query'], $form_state);
        }
        break;
      case 'cache_options':
        $plugin = $this->get_plugin('cache');
        if ($plugin) {
          $plugin->options_validate($form['cache_options'], $form_state);
        }
        break;
      case 'exposed_form_options':
        $plugin = $this->get_plugin('exposed_form');
        if ($plugin) {
          $plugin->options_validate($form['exposed_form_options'], $form_state);
        }
        break;
      case 'pager_options':
        $plugin = $this->get_plugin('pager');
        if ($plugin) {
          $plugin->options_validate($form['pager_options'], $form_state);
        }
        break;
    }

    foreach ($this->extender as $extender) {
      $extender->options_validate($form, $form_state);
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function options_submit(&$form, &$form_state) {
    // Not sure I like this being here, but it seems (?) like a logical place.
    $cache_plugin = $this->get_plugin('cache');
    if ($cache_plugin) {
      $cache_plugin->cache_flush();
    }

    $section = $form_state['section'];
    switch ($section) {
      case 'display_id':
        if (isset($form_state['values']['display_id'])) {
          $this->display->new_id = $form_state['values']['display_id'];
        }
        break;
      case 'display_title':
        $this->display->display_title = $form_state['values']['display_title'];
        $this->set_option('display_description', $form_state['values']['display_description']);
        break;
      case 'access':
        $access = $this->get_option('access');
        if ($access['type'] != $form_state['values']['access']['type']) {
          $plugin = views_get_plugin('access', $form_state['values']['access']['type']);
          if ($plugin) {
            $access = array('type' => $form_state['values']['access']['type']);
            $this->set_option('access', $access);
            if (!empty($plugin->definition['uses options'])) {
              views_ui_add_form_to_stack('display', $this->view, $this->display->id, array('access_options'));
            }
          }
        }

        break;
      case 'access_options':
        $plugin = views_get_plugin('access', $form_state['values'][$section]['type']);
        if ($plugin) {
          $plugin->options_submit($form['access_options'], $form_state);
          $this->set_option('access', $form_state['values'][$section]);
        }
        break;
      case 'cache':
        $cache = $this->get_option('cache');
        if ($cache['type'] != $form_state['values']['cache']['type']) {
          $plugin = views_get_plugin('cache', $form_state['values']['cache']['type']);
          if ($plugin) {
            $cache = array('type' => $form_state['values']['cache']['type']);
            $this->set_option('cache', $cache);
            if (!empty($plugin->definition['uses options'])) {
              views_ui_add_form_to_stack('display', $this->view, $this->display->id, array('cache_options'));
            }
          }
        }

        break;
      case 'cache_options':
        $plugin = views_get_plugin('cache', $form_state['values'][$section]['type']);
        if ($plugin) {
          $plugin->options_submit($form['cache_options'], $form_state);
          $this->set_option('cache', $form_state['values'][$section]);
        }
        break;
      case 'query':
        $plugin = $this->get_plugin('query');
        if ($plugin) {
          $plugin->options_submit($form['query']['options'], $form_state);
          $this->set_option('query', $form_state['values'][$section]);
        }
        break;

      case 'link_display':
        $this->set_option('link_url', $form_state['values']['link_url']);
      case 'title':
      case 'css_class':
      case 'display_comment':
        $this->set_option($section, $form_state['values'][$section]);
        break;
      case 'field_language':
        $this->set_option('field_language', $form_state['values']['field_language']);
        $this->set_option('field_language_add_to_query', $form_state['values']['field_language_add_to_query']);
        break;
      case 'use_ajax':
      case 'hide_attachment_summary':
        $this->set_option($section, (bool)$form_state['values'][$section]);
        break;
      case 'use_more':
        $this->set_option($section, intval($form_state['values'][$section]));
        $this->set_option('use_more_always', !intval($form_state['values']['use_more_always']));
        $this->set_option('use_more_text', $form_state['values']['use_more_text']);
      case 'distinct':
        $this->set_option($section, $form_state['values'][$section]);
        break;
      case 'group_by':
        $this->set_option($section, $form_state['values'][$section]);
        break;
      case 'row_plugin':
        // This if prevents resetting options to default if they don't change
        // the plugin.
        if ($this->get_option($section) != $form_state['values'][$section]) {
          $plugin = views_get_plugin('row', $form_state['values'][$section]);
          if ($plugin) {
            $this->set_option($section, $form_state['values'][$section]);
            $this->set_option('row_options', array());

            // send ajax form to options page if we use it.
            if (!empty($plugin->definition['uses options'])) {
              views_ui_add_form_to_stack('display', $this->view, $this->display->id, array('row_options'));
            }
          }
        }
        break;
      case 'style_plugin':
        // This if prevents resetting options to default if they don't change
        // the plugin.
        if ($this->get_option($section) != $form_state['values'][$section]) {
          $plugin = views_get_plugin('style', $form_state['values'][$section]);
          if ($plugin) {
            $this->set_option($section, $form_state['values'][$section]);
            $this->set_option('style_options', array());
            // send ajax form to options page if we use it.
            if (!empty($plugin->definition['uses options'])) {
              views_ui_add_form_to_stack('display', $this->view, $this->display->id, array('style_options'));
            }
          }
        }
        break;
      case 'style_options':
        $style = TRUE;
      case 'row_options':
        // if row, $style will be empty.
        $plugin = $this->get_plugin(empty($style) ? 'row' : 'style');
        if ($plugin) {
          $plugin->options_submit($form['options'][$section], $form_state);
        }
        $this->set_option($section, $form_state['values'][$section]);
        break;
      case 'exposed_block':
        $this->set_option($section, (bool) $form_state['values'][$section]);
        break;
      case 'exposed_form':
        $exposed_form = $this->get_option('exposed_form');
        if ($exposed_form['type'] != $form_state['values']['exposed_form']['type']) {
          $plugin = views_get_plugin('exposed_form', $form_state['values']['exposed_form']['type']);
          if ($plugin) {
            $exposed_form = array('type' => $form_state['values']['exposed_form']['type'], 'options' => array());
            $this->set_option('exposed_form', $exposed_form);
            if (!empty($plugin->definition['uses options'])) {
              views_ui_add_form_to_stack('display', $this->view, $this->display->id, array('exposed_form_options'));
            }
          }
        }

        break;
      case 'exposed_form_options':
        $plugin = $this->get_plugin('exposed_form');
        if ($plugin) {
          $exposed_form = $this->get_option('exposed_form');
          $plugin->options_submit($form['exposed_form_options'], $form_state);
          $exposed_form['options'] = $form_state['values'][$section];
          $this->set_option('exposed_form', $exposed_form);
        }
        break;
      case 'pager':
        $pager = $this->get_option('pager');
        if ($pager['type'] != $form_state['values']['pager']['type']) {
          $plugin = views_get_plugin('pager', $form_state['values']['pager']['type']);
          if ($plugin) {
            // Because pagers have very similar options, let's allow pagers to
            // try to carry the options over.
            $plugin->init($this->view, $this->display, $pager['options']);

            $pager = array('type' => $form_state['values']['pager']['type'], 'options' => $plugin->options);
            $this->set_option('pager', $pager);
            if (!empty($plugin->definition['uses options'])) {
              views_ui_add_form_to_stack('display', $this->view, $this->display->id, array('pager_options'));
            }
          }
        }

        break;
      case 'pager_options':
        $plugin = $this->get_plugin('pager');
        if ($plugin) {
          $pager = $this->get_option('pager');
          $plugin->options_submit($form['pager_options'], $form_state);
          $pager['options'] = $form_state['values'][$section];
          $this->set_option('pager', $pager);
        }
        break;
    }

    foreach ($this->extender as $extender) {
      $extender->options_submit($form, $form_state);
    }
  }

  /**
   * If override/revert was clicked, perform the proper toggle.
   */
  function options_override($form, &$form_state) {
    $this->set_override($form_state['section']);
  }

  /**
   * Flip the override setting for the given section.
   *
   * @param string $section
   *   Which option should be marked as overridden, for example "filters".
   * @param bool $new_state
   *   Select the new state of the option.
   *     - TRUE: Revert to default.
   *     - FALSE: Mark it as overridden.
   */
  function set_override($section, $new_state = NULL) {
    $options = $this->defaultable_sections($section);
    if (!$options) {
      return;
    }

    if (!isset($new_state)) {
      $new_state = empty($this->options['defaults'][$section]);
    }

    // For each option that is part of this group, fix our settings.
    foreach ($options as $option) {
      if ($new_state) {
        // Revert to defaults.
        unset($this->options[$option]);
        unset($this->display->display_options[$option]);
      }
      else {
        // copy existing values into our display.
        $this->options[$option] = $this->get_option($option);
        $this->display->display_options[$option] = $this->options[$option];
      }
      $this->options['defaults'][$option] = $new_state;
      $this->display->display_options['defaults'][$option] = $new_state;
    }
  }

  /**
   * Inject anything into the query that the display handler needs.
   */
  function query() {
    foreach ($this->extender as $extender) {
      $extender->query();
    }
  }

  /**
   * Not all display plugins will support filtering
   */
  function render_filters() { }

  /**
   * Not all display plugins will suppert pager rendering.
   */
  function render_pager() {
    return TRUE;
  }

  /**
   * Render the 'more' link
   */
  function render_more_link() {
    if ($this->use_more() && ($this->use_more_always() || (!empty($this->view->query->pager) && $this->view->query->pager->has_more_records()))) {
      $path = $this->get_path();

      if ($this->get_option('link_display') == 'custom_url' && $override_path = $this->get_option('link_url')) {
        $tokens = $this->get_arguments_tokens();
        $path = strtr($override_path, $tokens);
      }

      if ($path) {
        if (empty($override_path)) {
          $path = $this->view->get_url(NULL, $path);
        }
        $url_options = array();
        if (!empty($this->view->exposed_raw_input)) {
          $url_options['query'] = $this->view->exposed_raw_input;
        }
        $theme = views_theme_functions('views_more', $this->view, $this->display);
        $path = check_url(url($path, $url_options));

        return theme($theme, array('more_url' => $path, 'link_text' => check_plain($this->use_more_text()), 'view' => $this->view));
      }
    }
  }


  /**
   * Legacy functions.
   */

  /**
   * Render the header of the view.
   */
  function render_header() {
    $empty = !empty($this->view->result);
    return $this->render_area('header', $empty);
  }

  /**
   * Render the footer of the view.
   */
  function render_footer() {
    $empty = !empty($this->view->result);
    return $this->render_area('footer', $empty);
  }

  function render_empty() {
    return $this->render_area('empty');
  }

  /**
   * If this display creates a block, implement one of these.
   */
  function hook_block_list($delta = 0, $edit = array()) { return array(); }

  /**
   * If this display creates a page with a menu item, implement it here.
   */
  function hook_menu() { return array(); }

  /**
   * Render this display.
   */
  function render() {
    return theme($this->theme_functions(), array('view' => $this->view));
  }

  function render_area($area, $empty = FALSE) {
    $return = '';
    foreach ($this->get_handlers($area) as $area) {
      $return .= $area->render($empty);
    }
    return $return;
  }


  /**
   * Determine if the user has access to this display of the view.
   */
  function access($account = NULL) {
    if (!isset($account)) {
      global $user;
      $account = $user;
    }

    // Full override.
    if (user_access('access all views', $account)) {
      return TRUE;
    }

    $plugin = $this->get_plugin('access');
    if ($plugin) {
      return $plugin->access($account);
    }

    // fallback to all access if no plugin.
    return TRUE;
  }

  /**
   * Set up any variables on the view prior to execution. These are separated
   * from execute because they are extremely common and unlikely to be
   * overridden on an individual display.
   */
  function pre_execute() {
    $this->view->set_use_ajax($this->use_ajax());
    if ($this->use_more() && !$this->use_more_always()) {
      $this->view->get_total_rows = TRUE;
    }
    $this->view->init_handlers();
    if ($this->uses_exposed()) {
      $exposed_form = $this->get_plugin('exposed_form');
      $exposed_form->pre_execute();
    }

    foreach ($this->extender as $extender) {
      $extender->pre_execute();
    }
  }

  /**
   * When used externally, this is how a view gets run and returns
   * data in the format required.
   *
   * The base class cannot be executed.
   */
  function execute() { }

  /**
   * Fully render the display for the purposes of a live preview or
   * some other AJAXy reason.
   */
  function preview() { return $this->view->render(); }

  /**
   * Displays can require a certain type of style plugin. By default, they will
   * be 'normal'.
   */
  function get_style_type() { return 'normal'; }

  /**
   * Make sure the display and all associated handlers are valid.
   *
   * @return
   *   Empty array if the display is valid; an array of error strings if it is not.
   */
  function validate() {
    $errors = array();
    // Make sure displays that use fields HAVE fields.
    if ($this->uses_fields()) {
      $fields = FALSE;
      foreach ($this->get_handlers('field') as $field) {
        if (empty($field->options['exclude'])) {
          $fields = TRUE;
        }
      }

      if (!$fields) {
        $errors[] = t('Display "@display" uses fields but there are none defined for it or all are excluded.', array('@display' => $this->display->display_title));
      }
    }

    if ($this->has_path() && !$this->get_option('path')) {
      $errors[] = t('Display "@display" uses a path but the path is undefined.', array('@display' => $this->display->display_title));
    }

    // Validate style plugin
    $style = $this->get_plugin();
    if (empty($style)) {
      $errors[] = t('Display "@display" has an invalid style plugin.', array('@display' => $this->display->display_title));
    }
    else {
      $result = $style->validate();
      if (!empty($result) && is_array($result)) {
        $errors = array_merge($errors, $result);
      }
    }

    // Validate query plugin.
    $query = $this->get_plugin('query');
    $result = $query->validate();
    if (!empty($result) && is_array($result)) {
      $errors = array_merge($errors, $result);
    }

    // Validate handlers
    foreach (views_object_types() as $type => $info) {
      foreach ($this->get_handlers($type) as $handler) {
        $result = $handler->validate();
        if (!empty($result) && is_array($result)) {
          $errors = array_merge($errors, $result);
        }
      }
    }

    return $errors;
  }

  /**
   * Check if the provided identifier is unique.
   *
   * @param string $id
   *   The id of the handler which is checked.
   * @param string $identifier
   *   The actual get identifier configured in the exposed settings.
   *
   * @return bool
   *   Returns whether the identifier is unique on all handlers.
   *
   */
  function is_identifier_unique($id, $identifier) {
    foreach (views_object_types() as $type => $info) {
      foreach ($this->get_handlers($type) as $key => $handler) {
        if ($handler->can_expose() && $handler->is_exposed()) {
          if ($id != $key && $identifier == $handler->options['expose']['identifier']) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Provide the block system with any exposed widget blocks for this display.
   */
  function get_special_blocks() {
    $blocks = array();

    if ($this->uses_exposed_form_in_block()) {
      $delta = '-exp-' . $this->view->name . '-' . $this->display->id;
      $desc = t('Exposed form: @view-@display_id', array('@view' => $this->view->name, '@display_id' => $this->display->id));

      $blocks[$delta] = array(
        'info' => $desc,
        'cache' => DRUPAL_NO_CACHE,
      );
    }

    return $blocks;
  }

  /**
   * Render any special blocks provided for this display.
   */
  function view_special_blocks($type) {
    if ($type == '-exp') {
      // avoid interfering with the admin forms.
      if (arg(0) == 'admin' && arg(1) == 'structure' && arg(2) == 'views') {
        return;
      }
      $this->view->init_handlers();

      if ($this->uses_exposed() && $this->get_option('exposed_block')) {
        $exposed_form = $this->get_plugin('exposed_form');
        return array(
          'content' => $exposed_form->render_exposed_form(TRUE),
        );
      }
    }
  }

  /**
   * Override of export_option()
   *
   * Because displays do not want to export options that are NOT overridden from the
   * default display, we need some special handling during the export process.
   */
  function export_option($indent, $prefix, $storage, $option, $definition, $parents) {
    // The $prefix is wrong because we store our actual options a little differently:
    $prefix = '$handler->display->display_options';
    $output = '';
    if (!$parents && !$this->is_default_display()) {
      // Do not export items that are not overridden.
      if ($this->is_defaulted($option)) {
        return;
      }

      // If this is not defaulted and is overrideable, flip the switch to say this
      // is overridden.
      if ($this->defaultable_sections($option)) {
        $output .= $indent . $prefix . "['defaults']['$option'] = FALSE;\n";
      }
    }

    $output .= parent::export_option($indent, $prefix, $storage, $option, $definition, $parents);
    return $output;
  }

  /**
   * Special method to export items that have handlers.
   *
   * This method was specified in the option_definition() as the method to utilize to
   * export fields, filters, sort criteria, relationships and arguments. This passes
   * the export off to the individual handlers so that they can export themselves
   * properly.
   */
  function export_handler($indent, $prefix, $storage, $option, $definition, $parents) {
    $output = '';

    // cut the 's' off because the data is stored as the plural form but we need
    // the singular form. Who designed that anyway? Oh yeah, I did. :(
    if ($option != 'header' && $option != 'footer' && $option != 'empty') {
      $type = substr($option, 0, -1);
    }
    else {
      $type = $option;
    }
    $types = views_object_types();
    foreach ($storage[$option] as $id => $info) {
      if (!empty($types[$type]['type'])) {
        $handler_type = $types[$type]['type'];
      }
      else {
        $handler_type = $type;
      }
      // If aggregation is on, the group type might override the actual
      // handler that is in use. This piece of code checks that and,
      // if necessary, sets the override handler.
      $override = NULL;
      if ($this->use_group_by() && !empty($info['group_type'])) {
        if (empty($this->view->query)) {
          $this->view->init_query();
        }
        $aggregate = $this->view->query->get_aggregation_info();
        if (!empty($aggregate[$info['group_type']]['handler'][$type])) {
          $override = $aggregate[$info['group_type']]['handler'][$type];
        }
      }
      $handler = views_get_handler($info['table'], $info['field'], $handler_type, $override);
      if ($handler) {
        $handler->init($this->view, $info);
        $output .= $indent . '/* ' . $types[$type]['stitle'] . ': ' . $handler->ui_name() . " */\n";
        $output .= $handler->export_options($indent, $prefix . "['$option']['$id']");
      }

      // Prevent reference problems.
      unset($handler);
    }

    return $output;
  }

  /**
   * Special handling for the style export.
   *
   * Styles are stored as style_plugin and style_options or row_plugin and
   * row_options accordingly. The options are told not to export, and the
   * export for the plugin should export both.
   */
  function export_style($indent, $prefix, $storage, $option, $definition, $parents) {
    $output = '';
    $style_plugin = $this->get_plugin();
    if ($option == 'style_plugin') {
      $type = 'style';
      $options_field = 'style_options';
      $plugin = $style_plugin;
    }
    else {
      if (!$style_plugin || !$style_plugin->uses_row_plugin()) {
        return;
      }

      $type = 'row';
      $options_field = 'row_options';
      $plugin = $this->get_plugin('row');
      // If the style plugin doesn't use row plugins, don't even bother.
    }

    if ($plugin) {
      // Write which plugin to use.
      $value = $this->get_option($option);
      $output .= $indent . $prefix . "['$option'] = '$value';\n";

      // Pass off to the plugin to export itself.
      $output .= $plugin->export_options($indent, $prefix . "['$options_field']");
    }

    return $output;
  }

  /**
   * Special handling for plugin export
   *
   * Plugins other than styles are stored in array with 'type' being the key
   * to the plugin. For modern plugins, the options are stored in the 'options'
   * array, but for legacy plugins (access and cache) options are stored as
   * siblings to the type.
   */
  function export_plugin($indent, $prefix, $storage, $option, $definition, $parents) {
    $output = '';
    $plugin_type = end($parents);
    $plugin = $this->get_plugin($plugin_type);
    if ($plugin) {
      // Write which plugin to use.
      $value = $storage[$option];
      $new_prefix = $prefix . "['$plugin_type']";

      $output .= $indent . $new_prefix . "['$option'] = '$value';\n";

      if ($plugin_type != 'access' && $plugin_type!= 'cache') {
        $new_prefix .= "['options']";
      }

      // Pass off to the plugin to export itself.
      $output .= $plugin->export_options($indent, $new_prefix);
    }

    return $output;
  }

  function unpack_style($indent, $prefix, $storage, $option, $definition, $parents) {
    $output = '';
    $style_plugin = $this->get_plugin();
    if ($option == 'style_plugin') {
      $type = 'style';
      $options_field = 'style_options';
      $plugin = $style_plugin;
    }
    else {
      if (!$style_plugin || !$style_plugin->uses_row_plugin()) {
        return;
      }

      $type = 'row';
      $options_field = 'row_options';
      $plugin = $this->get_plugin('row');
      // If the style plugin doesn't use row plugins, don't even bother.
    }

    if ($plugin) {
      return $plugin->unpack_translatables($translatable, $parents);
    }
  }

  /**
   * Special handling for plugin unpacking.
   */
  function unpack_plugin(&$translatable, $storage, $option, $definition, $parents) {
    $plugin_type = end($parents);
    $plugin = $this->get_plugin($plugin_type);
    if ($plugin) {
      // Write which plugin to use.
      return $plugin->unpack_translatables($translatable, $parents);
    }
  }

    /**
   * Special method to unpack items that have handlers.
   *
   * This method was specified in the option_definition() as the method to utilize to
   * export fields, filters, sort criteria, relationships and arguments. This passes
   * the export off to the individual handlers so that they can export themselves
   * properly.
   */
  function unpack_handler(&$translatable, $storage, $option, $definition, $parents) {
    $output = '';

    // cut the 's' off because the data is stored as the plural form but we need
    // the singular form. Who designed that anyway? Oh yeah, I did. :(
    if ($option != 'header' && $option != 'footer' && $option != 'empty') {
      $type = substr($option, 0, -1);
    }
    else {
      $type = $option;
    }
    $types = views_object_types();
    foreach ($storage[$option] as $id => $info) {
      if (!empty($types[$type]['type'])) {
        $handler_type = $types[$type]['type'];
      }
      else {
        $handler_type = $type;
      }
      $handler = views_get_handler($info['table'], $info['field'], $handler_type);
      if ($handler) {
        $handler->init($this->view, $info);
        $handler->unpack_translatables($translatable, array_merge($parents, array($type, $info['table'], $info['id'])));
      }

      // Prevent reference problems.
      unset($handler);
    }

    return $output;
  }

  /**
   * Provide some helpful text for the arguments.
   * The result should contain of an array with
   *   - filter value present: The title of the fieldset in the argument
   *     where you can configure what should be done with a given argument.
   *   - filter value not present: The tiel of the fieldset in the argument
   *     where you can configure what should be done if the argument does not
   *     exist.
   *   - description: A description about how arguments comes to the display.
   *     For example blocks don't get it from url.
   */
  function get_argument_text() {
    return array(
      'filter value not present' => t('When the filter value is <em>NOT</em> available'),
      'filter value present' => t('When the filter value <em>IS</em> available or a default is provided'),
      'description' => t("This display does not have a source for contextual filters, so no contextual filter value will be available unless you select 'Provide default'."),
    );
  }

  /**
   * Provide some helpful text for pagers.
   *
   * The result should contain of an array within
   *   - items per page title
   */
  function get_pager_text() {
    return array(
      'items per page title' => t('Items to display'),
      'items per page description' => t('The number of items to display. Enter 0 for no limit.')
    );
  }
}


/**
 * @}
 */
