<?php

namespace Drupal\mysqli\Hook;

use Drupal\Core\Database\Database;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mysql\RequirementsTrait;

/**
 * Hook implementations for mysqli.
 */
class MysqliHooks {

  use RequirementsTrait;
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
        $output .= '<p>' . $this->t('The MySQLi module provides the connection between Drupal and a MySQL, MariaDB or equivalent database using the mysqli PHP extension. For more information, see the <a href=":mysqli">online documentation for the MySQLi module</a>.', [':mysqli' => 'https://www.drupal.org/docs/develop/core-modules-and-themes/core-modules/mysqli-module']) . '</p>';
        return $output;

    }
    return NULL;
  }

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    if (!Database::isActiveConnection()) {
      return [];
    }

    $connection = Database::getConnection();
    // Only show requirements when MySQLi is the default database connection.
    if (!($connection->driver() === 'mysqli' && $connection->getProvider() === 'mysqli')) {
      return [];
    }

    return $this->getRuntimeRequirements($connection);
  }

}
