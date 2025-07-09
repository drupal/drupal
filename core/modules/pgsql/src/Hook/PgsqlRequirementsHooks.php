<?php

namespace Drupal\pgsql\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\pgsql\Install\Requirements\PgsqlRequirements;

/**
 * Hook implementations for pgsql module.
 */
class PgsqlRequirementsHooks {

  /**
   * Implements hook_update_requirements().
   *
   * Implements hook_runtime_requirements().
   */
  #[Hook('update_requirements')]
  #[Hook('runtime_requirements')]
  public function checkRequirements(): array {
    // We want the identical check from the install time requirements.
    return PgsqlRequirements::getRequirements();
  }

}
