<?php

namespace Drupal\pgsql\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for pgsql.
 */
class PgsqlHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.pgsql':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The PostgreSQL module provides the connection between Drupal and a PostgreSQL database. For more information, see the <a href=":pgsql">online documentation for the PostgreSQL module</a>.', [
          ':pgsql' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/postgresql-module',
        ]) . '</p>';
        return $output;
    }
  }

}
