<?php

namespace Drupal\Core\Plugin;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Provides a trait for plugin managers that allow filtering plugin definitions.
 */
trait FilteredPluginManagerTrait {

  use ContextAwarePluginManagerTrait;

  /**
   * Implements \Drupal\Core\Plugin\FilteredPluginManagerInterface::getFilteredDefinitions().
   */
  public function getFilteredDefinitions($consumer, $contexts = NULL, array $extra = []) {
    if (!is_null($contexts)) {
      $definitions = $this->getDefinitionsForContexts($contexts);
    }
    else {
      $definitions = $this->getDefinitions();
    }

    $type = $this->getType();
    $hooks = [];
    $hooks[] = "plugin_filter_{$type}";
    $hooks[] = "plugin_filter_{$type}__{$consumer}";
    $this->moduleHandler()->alter($hooks, $definitions, $extra, $consumer);
    $this->themeManager()->alter($hooks, $definitions, $extra, $consumer);
    return $definitions;
  }

  /**
   * A string identifying the plugin type.
   *
   * This string should be unique and generally will correspond to the string
   * used by the discovery, e.g. the annotation class or the YAML file name.
   *
   * @return string
   *   A string identifying the plugin type.
   */
  abstract protected function getType();

  /**
   * Wraps the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  protected function moduleHandler() {
    if (property_exists($this, 'moduleHandler') && $this->moduleHandler instanceof ModuleHandlerInterface) {
      return $this->moduleHandler;
    }

    return \Drupal::service('module_handler');
  }

  /**
   * Wraps the theme manager.
   *
   * @return \Drupal\Core\Theme\ThemeManagerInterface
   *   The theme manager.
   */
  protected function themeManager() {
    if (property_exists($this, 'themeManager') && $this->themeManager instanceof ThemeManagerInterface) {
      return $this->themeManager;
    }

    return \Drupal::service('theme.manager');
  }

}
