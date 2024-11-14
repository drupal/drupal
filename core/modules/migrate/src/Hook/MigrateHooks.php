<?php

namespace Drupal\migrate\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for migrate.
 */
class MigrateHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.migrate':
        $output = '<h2>' . t('About') . '</h2>';
        $output .= '<p>';
        $output .= t('The Migrate module provides a framework for migrating data, usually from an external source into your site. It does not provide a user interface. For more information, see the <a href=":migrate">online documentation for the Migrate module</a>.', [':migrate' => 'https://www.drupal.org/documentation/modules/migrate']);
        $output .= '</p>';
        return $output;
    }
  }

}
