<?php

declare(strict_types=1);

namespace Drupal\mysql\Hook;

use Drupal\Core\Database\Database;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mysql\RequirementsTrait;

/**
 * Requirements for the MySQL module.
 */
class MysqlRequirements {

  use RequirementsTrait;
  use StringTranslationTrait;

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    if (!Database::isActiveConnection()) {
      return [];
    }

    $connection = Database::getConnection();
    // Only show requirements when MySQL is the default database connection.
    if (!($connection->driver() === 'mysql' && $connection->getProvider() === 'mysql')) {
      return [];
    }

    return $this->getRuntimeRequirements($connection);
  }

}
