<?php

/**
 * @file
 * Contains \Drupal\views\Views.
 */

namespace Drupal\views;

use Drupal\Component\Utility\String;

/**
 * Static service container wrapper for views.
 */
class Views {

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
   * @return \Drupal\views\ViewsData
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
    $view = \Drupal::service('entity.manager')->getStorageController('view')->load($id);
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
  public static function fetchPluginNames($type, $key = NULL, array $base = array()) {
    $definitions = static::pluginManager($type)->getDefinitions();
    $plugins = array();

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
    $plugins = array();
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
   * Return a list of all views and display IDs that have a particular
   * setting in their display's plugin settings.
   *
   * @param string $type
   *   A flag from the display plugin definitions (e.g, 'uses_hook_menu').
   *
   * @return array
   *   A list of arrays containing the $view and $display_id.
   * @code
   * array(
   *   array($view, $display_id),
   *   array($view, $display_id),
   * );
   * @endcode
   */
  public static function getApplicableViews($type) {
    // Get all display plugins which provides the type.
    $display_plugins = static::pluginManager('display')->getDefinitions();
    $ids = array();
    foreach ($display_plugins as $id => $definition) {
      if (!empty($definition[$type])) {
        $ids[$id] = $id;
      }
    }

    $entity_ids = \Drupal::service('entity.query')->get('view')
      ->condition('status', TRUE)
      ->condition("display.*.display_plugin", $ids, 'IN')
      ->execute();

    $result = array();
    foreach (\Drupal::entityManager()->getStorageController('view')->loadMultiple($entity_ids) as $view) {
      // Check each display to see if it meets the criteria and is enabled.
      $executable = $view->getExecutable();
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $id => $handler) {
        if (!empty($handler->definition[$type]) && $handler->isEnabled()) {
          $result[] = array($executable, $id);
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
    return \Drupal::entityManager()->getStorageController('view')->loadMultiple();
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

    return \Drupal::entityManager()->getStorageController('view')->loadMultiple($query);
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

    return \Drupal::entityManager()->getStorageController('view')->loadMultiple($query);
  }

  /**
   * Returns an array of view as options array, that can be used by select,
   * checkboxes and radios as #options.
   *
   * @param bool $views_only
   *  If TRUE, only return views, not displays.
   * @param string $filter
   *  Filters the views on status. Can either be 'all' (default), 'enabled' or
   *  'disabled'
   * @param mixed $exclude_view
   *  view or current display to exclude
   *  either a
   *  - views object (containing $exclude_view->storage->name and $exclude_view->current_display)
   *  - views name as string:  e.g. my_view
   *  - views name and display id (separated by ':'): e.g. my_view:default
   * @param bool $optgroup
   *  If TRUE, returns an array with optgroups for each view (will be ignored for
   *  $views_only = TRUE). Can be used by select
   * @param bool $sort
   *  If TRUE, the list of views is sorted ascending.
   *
   * @return array
   *  an associative array for use in select.
   *  - key: view name and display id separated by ':', or the view name only
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
        return array();
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

    $options = array();
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
              $options[$id][$id . ':' . $display['id']] = t('@view : @display', array('@view' => $id, '@display' => $display['id']));
            }
            else {
              $options[$id . ':' . $display['id']] = t('View: @view - Display: @display', array('@view' => $id, '@display' => $display['id']));
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
    $plugins = array();
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
            $plugins[$key] = array(
              'type' => $type,
              'title' => String::checkPlain($info[$name]['title']),
              'provider' => String::checkPlain($info[$name]['provider']),
              'views' => array(),
            );
          }

          // Add this view to the list for this plugin.
          $plugins[$key]['views'][$view->id()] = $view->id();
        }
      }
    }
    return $plugins;
  }

}
