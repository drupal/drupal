<?php

declare(strict_types=1);

namespace Drupal\config_import_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_import_test.
 */
class ConfigImportTestHooks {

  /**
   * Implements hook_config_import_steps_alter().
   */
  #[Hook('config_import_steps_alter')]
  public function configImportStepsAlter(&$sync_steps): void {
    $sync_steps[] = '_config_import_test_config_import_steps_alter';
  }

}
