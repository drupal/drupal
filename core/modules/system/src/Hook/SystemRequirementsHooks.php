<?php

namespace Drupal\system\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\system\Install\Requirements\SystemRequirements;

/**
 * Requirements hook implementations for system module.
 */
class SystemRequirementsHooks {

  /**
   * Implements hook_update_requirements().
   */
  #[Hook('update_requirements')]
  public function updateRequirements(): array {
    return SystemRequirements::checkRequirements('update');
  }

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    return SystemRequirements::checkRequirements('runtime');
  }

}
