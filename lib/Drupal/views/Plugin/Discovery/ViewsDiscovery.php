<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Discovery\ViewsDiscovery.
 */

namespace Drupal\views\Plugin\Discovery;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Discovery interface which supports the hook_views_plugins mechanism.
 */
class ViewsDiscovery extends AnnotatedClassDiscovery {
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    foreach ($definitions as $definition) {
      // @todo: Allow other modules to write views plugins
      $module_dir = $module = 'views';
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

      $definition['module'] = $module_dir;
      if (!isset($definition['theme path'])) {
        $definition['theme path'] = $theme_path;
      }
      if (!isset($definition['theme file'])) {
        $definition['theme file'] = $theme_file;
      }
      if (!isset($definition['path'])) {
        $definition['path'] = $path;
      }
      if (!isset($definition['parent'])) {
        $definition['parent'] = 'parent';
      }

      // merge the new data in
      $definitions[$definition['plugin_id']] = $definition;
    }

    return $definitions;
  }
}
