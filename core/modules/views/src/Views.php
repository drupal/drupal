<?php

namespace Drupal\views;

/**
 * Static service container wrapper for views.
 */
class Views {

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected static $translationManager;

  /**
   * A static cache for handler types data.
   *
   * @var array
   */
  protected static $handlerTypes;

  /**
   * A list of all available views plugin types.
   *
   * @var array
   */
  protected static $plugins = [
    'access' => 'plugin',
    'area' => 'handler',
    'argument' => 'handler',
    'argument_default' => 'plugin',
    'argument_validator' => 'plugin',
    'cache' => 'plugin',
    'display_extender' => 'plugin',
    'display' => 'plugin',
    'exposed_form' => 'plugin',
    'field' => 'handler',
    'filter' => 'handler',
    'join' => 'plugin',
    'pager' => 'plugin',
    'query' => 'plugin',
    'relationship' => 'handler',
    'row' => 'plugin',
    'sort' => 'handler',
    'style' => 'plugin',
    'wizard' => 'plugin',
  ];

  /**
   * Returns the views data service.
   *
   * @return \Drupal\views\ViewsData
   *   Returns a views data cache object.
   */
  public static function viewsData() {
    return \Drupal::service('views.views_data');
  }

  /**
   * Returns the views data helper service.
   *
   * @return \Drupal\views\ViewsDataHelper
   *   Returns a views data helper object.
   */
  public static function viewsDataHelper() {
    return \Drupal::service('views.views_data_helper');
  }

  /**
   * Returns the view executable factory service.
   *
   * @return \Drupal\views\ViewExecutableFactory
   *   Returns a views executable factory.
   */
  public static function executableFactory() {
    return \Drupal::service('views.executable');
  }

  /**
   * Returns the view analyzer.
   *
   * @return \Drupal\views\Analyzer
   *   Returns a view analyzer object.
   */
  public static function analyzer() {
    return \Drupal::service('views.analyzer');
  }

  /**
   * Returns the plugin manager for a certain views plugin type.
   *
   * @param string $type
   *   The plugin type, for example filter.
   *
   * @return \Drupal\views\Plugin\ViewsPluginManager
   */
  public static function pluginManager($type) {
    return \Drupal::service('plugin.manager.views.' . $type);
  }

  /**
   * Returns the plugin manager for a certain views handler type.
   *
   * @return \Drupal\views\Plugin\ViewsHandlerManager
   */
  public static function handlerManager($type) {
    return \Drupal::service('plugin.manager.views.' . $type);
  }

  /**
   * Loads a view from configuration and returns its executable object.
   *
   * @param string $id
   *   The view ID to load.
   *
   * @return \Drupal\views\ViewExecutable
   *   A view executable instance, from the loaded entity.
   */
  public static function getView($id) {
    $view = \Drupal::service('entity.manager')->getStorage('view')->load($id);
    if ($view) {
      return static::executableFactory()->get($view);
    }
  }

  /**
   * Fetches a list of all base tables available
   *
   * @param string $type
   *   Either 'display', 'style' or 'row'.
   * @param string $key
   *   For style plugins, this is an optional type to restrict to. May be
   *   'normal', 'summary', 'feed' or others based on the needs of the display.
   * @param array $base
   *   An array of possible base tables.
   *
   * @return
   *   A keyed array of in the form of 'base_table' => 'Description'.
   */
  public static function fetchPluginNames($type, $key = NULL, array $base = []) {
    $definitions = static::pluginManager($type)->getDefinitions();
    $plugins = [];

    foreach ($definitions as $id => $plugin) {
      // Skip plugins that don't conform to our key, if they have one.
      if ($key && isset($plugin['display_types']) && !in_array($key, $plugin['display_types'])) {
        continue;
      }

      if (empty($plugin['no_ui']) && (empty($base) || empty($plugin['base']) || array_intersect($base, $plugin['base']))) {
        $plugins[$id] = $plugin['title'];
      }
    }

    if (!empty($plugins)) {
      asort($plugins);
      return $plugins;
    }

    return $plugins;
  }

  /**
   * Gets all the views plugin definitions.
   *
   * @return array
   *   An array of plugin definitions for all types.
   */
  public static function getPluginDefinitions() {
    $plugins = [];
    foreach (ViewExecutable::getPluginTypes() as $plugin_type) {
      $plugins[$plugin_type] = static::pluginManager($plugin_type)->getDefinitions();
    }

    return $plugins;
  }

  /**
   * Gets enabled display extenders.
   */
  public static function getEnabledDisplayExtenders() {
    $enabled = array_filter((array) \Drupal::config('views.settings')->get('display_extenders'));

    return array_combine($enabled, $enabled);
  }

  /**
   * Return a list of all view IDs and display IDs that have a particular
   * setting in their display's plugin settings.
   *
   * @param string $type
   *   A flag from the display plugin definitions (e.g, 'uses_menu_links').
   *
   * @return array
   *   A list of arrays containing the $view_id and $display_id.
   * @code
   * array(
   *   array($view_id, $display_id),
   *   array($view_id, $display_id),
   * );
   * @endcode
   */
  public static function getApplicableViews($type) {
    // Get all display plugins which provides the type.
    $display_plugins = static::pluginManager('display')->getDefinitions();

    $plugin_ids = [];
    foreach ($display_plugins as $id => $definition) {
      if (!empty($definition[$type])) {
        $plugin_ids[$id] = $id;
      }
    }

    $entity_ids = \Drupal::entityQuery('view')
      ->condition('status', TRUE)
      ->condition("display.*.display_plugin", $plugin_ids, 'IN')
      ->execute();

    $result = [];
    foreach (\Drupal::entityTypeManager()->getStorage('view')->loadMultiple($entity_ids) as $view) {
      // Check each display to see if it meets the criteria and is enabled.

      foreach ($view->get('display') as $id => $display) {
        // If the key doesn't exist, enabled is assumed.
        $enabled = !empty($display['display_options']['enabled']) || !array_key_exists('enabled', $display['display_options']);

        if ($enabled && in_array($display['display_plugin'], $plugin_ids)) {
          $result[] = [$view->id(), $id];
        }
      }
    }

    return $result;
  }

  /**
   * Returns an array of all views as fully loaded $view objects.
   *
   * @return \Drupal\views\Entity\View[]
   *   An array of loaded view entities.
   */
  public static function getAllViews() {
    return \Drupal::entityManager()->getStorage('view')->loadMultiple();
  }

  /**
   * Returns an array of all enabled views.
   *
   * @return \Drupal\views\Entity\View[]
   *   An array of loaded enabled view entities.
   */
  public static function getEnabledViews() {
    $query = \Drupal::entityQuery('view')
      ->condition('status', TRUE)
      ->execute();

    return \Drupal::entityManager()->getStorage('view')->loadMultiple($query);
  }

  /**
   * Returns an array of all disabled views.
   *
   * @return \Drupal\views\Entity\View[]
   *   An array of loaded disabled view entities.
   */
  public static function getDisabledViews() {
    $query = \Drupal::entityQuery('view')
      ->condition('status', FALSE)
      ->execute();

    return \Drupal::entityManager()->getStorage('view')->loadMultiple($query);
  }

  /**
   * Returns an array of view as options array, that can be used by select,
   * checkboxes and radios as #options.
   *
   * @param bool $views_only
   *   If TRUE, only return views, not displays.
   * @param string $filter
   *   Filters the views on status. Can either be 'all' (default), 'enabled' or
   *   'disabled'
   * @param mixed $exclude_view
   *   View or current display to exclude.
   *   Either a:
   *   - views object (containing $exclude_view->storage->name and $exclude_view->current_display)
   *   - views name as string:  e.g. my_view
   *   - views name and display id (separated by ':'): e.g. my_view:default
   * @param bool $optgroup
   *   If TRUE, returns an array with optgroups for each view (will be ignored for
   *   $views_only = TRUE). Can be used by select
   * @param bool $sort
   *   If TRUE, the list of views is sorted ascending.
   *
   * @return array
   *   An associative array for use in select.
   *   - key: view name and display id separated by ':', or the view name only.
   */
  public static function getViewsAsOptions($views_only = FALSE, $filter = 'all', $exclude_view = NULL, $optgroup = FALSE, $sort = FALSE) {

    // Filter the big views array.
    switch ($filter) {
      case 'all':
      case 'disabled':
      case 'enabled':
        $filter = ucfirst($filter);
        $views = call_user_func("static::get{$filter}Views");
        break;
      default:
        return [];
    }

    // Prepare exclude view strings for comparison.
    if (empty($exclude_view)) {
      $exclude_view_name = '';
      $exclude_view_display = '';
    }
    elseif (is_object($exclude_view)) {
      $exclude_view_name = $exclude_view->storage->id();
      $exclude_view_display = $exclude_view->current_display;
    }
    else {
      // Append a ':' to the $exclude_view string so we always have more than one
      // item to explode.
      list($exclude_view_name, $exclude_view_display) = explode(':', "$exclude_view:");
    }

    $options = [];
    foreach ($views as $view) {
      $id = $view->id();
      // Return only views.
      if ($views_only && $id != $exclude_view_name) {
        $options[$id] = $view->label();
      }
      // Return views with display ids.
      else {
        foreach ($view->get('display') as $display_id => $display) {
          if (!($id == $exclude_view_name && $display_id == $exclude_view_display)) {
            if ($optgroup) {
              $options[$id][$id . ':' . $display['id']] = t('@view : @display', ['@view' => $id, '@display' => $display['id']]);
            }
            else {
              $options[$id . ':' . $display['id']] = t('View: @view - Display: @display', ['@view' => $id, '@display' => $display['id']]);
            }
          }
        }
      }
    }
    if ($sort) {
      ksort($options);
    }
    return $options;
  }

  /**
   * Returns a list of plugins and metadata about them.
   *
   * @return array
   *   An array keyed by PLUGIN_TYPE:PLUGIN_NAME, like 'display:page' or
   *   'pager:full', containing an array with the following keys:
   *   - title: The plugin's title.
   *   - type: The plugin type.
   *   - module: The module providing the plugin.
   *   - views: An array of enabled Views that are currently using this plugin,
   *     keyed by machine name.
   */
  public static function pluginList() {
    $plugin_data = static::getPluginDefinitions();
    $plugins = [];
    foreach (static::getEnabledViews() as $view) {
      foreach ($view->get('display') as $display) {
        foreach ($plugin_data as $type => $info) {
          if ($type == 'display' && isset($display['display_plugin'])) {
            $name = $display['display_plugin'];
          }
          elseif (isset($display['display_options']["{$type}_plugin"])) {
            $name = $display['display_options']["{$type}_plugin"];
          }
          elseif (isset($display['display_options'][$type]['type'])) {
            $name = $display['display_options'][$type]['type'];
          }
          else {
            continue;
          }

          // Key first by the plugin type, then the name.
          $key = $type . ':' . $name;
          // Add info for this plugin.
          if (!isset($plugins[$key])) {
            $plugins[$key] = [
              'type' => $type,
              'title' => $info[$name]['title'],
              'provider' => $info[$name]['provider'],
              'views' => [],
            ];
          }

          // Add this view to the list for this plugin.
          $plugins[$key]['views'][$view->id()] = $view->id();
        }
      }
    }
    return $plugins;
  }

  /**
   * Provide a list of views handler types used in a view, with some information
   * about them.
   *
   * @return array
   *   An array of associative arrays containing:
   *   - title: The title of the handler type.
   *   - ltitle: The lowercase title of the handler type.
   *   - stitle: A singular title of the handler type.
   *   - lstitle: A singular lowercase title of the handler type.
   *   - plural: Plural version of the handler type.
   *   - (optional) type: The actual internal used handler type. This key is
   *     just used for header,footer,empty to link to the internal type: area.
   */
  public static function getHandlerTypes() {
    // Statically cache this so translation only occurs once per request for all
    // of these values.
    if (!isset(static::$handlerTypes)) {
      static::$handlerTypes = [
        'field' => [
          // title
          'title' => static::t('Fields'),
          // Lowercase title for mid-sentence.
          'ltitle' => static::t('fields'),
          // Singular title.
          'stitle' => static::t('Field'),
          // Singular lowercase title for mid sentence
          'lstitle' => static::t('field'),
          'plural' => 'fields',
        ],
        'argument' => [
          'title' => static::t('Contextual filters'),
          'ltitle' => static::t('contextual filters'),
          'stitle' => static::t('Contextual filter'),
          'lstitle' => static::t('contextual filter'),
          'plural' => 'arguments',
        ],
        'sort' => [
          'title' => static::t('Sort criteria'),
          'ltitle' => static::t('sort criteria'),
          'stitle' => static::t('Sort criterion'),
          'lstitle' => static::t('sort criterion'),
          'plural' => 'sorts',
        ],
        'filter' => [
          'title' => static::t('Filter criteria'),
          'ltitle' => static::t('filter criteria'),
          'stitle' => static::t('Filter criterion'),
          'lstitle' => static::t('filter criterion'),
          'plural' => 'filters',
        ],
        'relationship' => [
          'title' => static::t('Relationships'),
          'ltitle' => static::t('relationships'),
          'stitle' => static::t('Relationship'),
          'lstitle' => static::t('Relationship'),
          'plural' => 'relationships',
        ],
        'header' => [
          'title' => static::t('Header'),
          'ltitle' => static::t('header'),
          'stitle' => static::t('Header'),
          'lstitle' => static::t('Header'),
          'plural' => 'header',
          'type' => 'area',
        ],
        'footer' => [
          'title' => static::t('Footer'),
          'ltitle' => static::t('footer'),
          'stitle' => static::t('Footer'),
          'lstitle' => static::t('Footer'),
          'plural' => 'footer',
          'type' => 'area',
        ],
        'empty' => [
          'title' => static::t('No results behavior'),
          'ltitle' => static::t('no results behavior'),
          'stitle' => static::t('No results behavior'),
          'lstitle' => static::t('No results behavior'),
          'plural' => 'empty',
          'type' => 'area',
        ],
      ];
    }

    return static::$handlerTypes;
  }

  /**
   * Returns a list of plugin types.
   *
   * @param string $type
   *   (optional) filter the list of plugins by type. Available options are
   *   'plugin' or 'handler'.
   *
   * @return array
   *   An array of plugin types.
   */
  public static function getPluginTypes($type = NULL) {
    if ($type === NULL) {
      return array_keys(static::$plugins);
    }

    if (!in_array($type, ['plugin', 'handler'])) {
      throw new \Exception('Invalid plugin type used. Valid types are "plugin" or "handler".');
    }

    return array_keys(array_filter(static::$plugins, function($plugin_type) use ($type) {
      return $plugin_type == $type;
    }));
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected static function t($string, array $args = [], array $options = []) {
    if (empty(static::$translationManager)) {
      static::$translationManager = \Drupal::service('string_translation');
    }

    return static::$translationManager->translate($string, $args, $options);
  }

}
