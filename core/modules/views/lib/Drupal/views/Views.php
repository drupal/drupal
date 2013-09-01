<?php

/**
 * @file
 * Contains \Drupal\views\Views.
 */

namespace Drupal\views;

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

}
