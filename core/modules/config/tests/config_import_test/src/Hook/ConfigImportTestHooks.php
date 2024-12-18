<?php

declare(strict_types=1);

namespace Drupal\config_import_test\Hook;

use Drupal\Core\Config\ConfigImporter;
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
    $sync_steps[] = [$this, 'stepAlter'];
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules, $is_syncing): void {
    \Drupal::state()->set('config_import_test_modules_installed.list', $modules);
  }

  /**
   * Implements configuration synchronization step added by an alter for testing.
   *
   * @param array $context
   *   The batch context.
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The configuration importer.
   */
  public function stepAlter(&$context, ConfigImporter $config_importer): void {
    $GLOBALS['hook_config_test']['config_import_steps_alter'] = TRUE;
    if (\Drupal::state()->get('config_import_steps_alter.error', FALSE)) {
      $context['results']['errors'][] = '_config_import_test_config_import_steps_alter batch error';
      $config_importer->logError('_config_import_test_config_import_steps_alter ConfigImporter error');
    }
    $context['finished'] = 1;
  }

}
