<?php

namespace Drupal\sqlite\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for sqlite.
 */
class SqliteHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.sqlite':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The SQLite module provides the connection between Drupal and a SQLite database. For more information, see the <a href=":sqlite">online documentation for the SQLite module</a>.', [
          ':sqlite' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/sqlite-module',
        ]) . '</p>';
        return $output;
    }
    return NULL;
  }

}
