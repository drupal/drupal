<?php

namespace Drupal\mysqli\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for mysqli.
 */
class MysqliHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.mysqli':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The MySQLi module provides the connection between Drupal and a MySQL, MariaDB or equivalent database using the mysqli PHP extension. For more information, see the <a href=":mysqli">online documentation for the MySQLi module</a>.', [':mysqli' => 'https://www.drupal.org/documentation/modules/mysqli']) . '</p>';
        return $output;

    }
    return NULL;
  }

}
