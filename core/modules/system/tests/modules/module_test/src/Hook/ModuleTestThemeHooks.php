<?php

declare(strict_types=1);

namespace Drupal\module_test\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for module_test.
 */
class ModuleTestThemeHooks {

  /**
   * Implements hook_system_info_alter().
   *
   * Manipulate module dependencies to test dependency chains.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    if (\Drupal::state()->get('module_test.dependency') == 'missing dependency') {
      if ($file->getName() == 'dblog') {
        // Make dblog module depend on config.
        $info['dependencies'][] = 'config';
      }
      elseif ($file->getName() == 'config') {
        // Make config module depend on a non-existing module.
        $info['dependencies'][] = 'foo';
      }
    }
    elseif (\Drupal::state()->get('module_test.dependency') == 'dependency') {
      if ($file->getName() == 'dblog') {
        // Make dblog module depend on config.
        $info['dependencies'][] = 'config';
      }
      elseif ($file->getName() == 'config') {
        // Make config module depend on help module.
        $info['dependencies'][] = 'help';
      }
      elseif ($file->getName() == 'entity_test') {
        // Make entity test module depend on help module.
        $info['dependencies'][] = 'help';
      }
    }
    elseif (\Drupal::state()->get('module_test.dependency') == 'version dependency') {
      if ($file->getName() == 'dblog') {
        // Make dblog module depend on config.
        $info['dependencies'][] = 'config';
      }
      elseif ($file->getName() == 'config') {
        // Make config module depend on a specific version of help module.
        $info['dependencies'][] = 'help (1.x)';
      }
      elseif ($file->getName() == 'help') {
        // Set help module to a version compatible with the above.
        $info['version'] = '8.x-1.0';
      }
    }
    if ($file->getName() == 'stark' && $type == 'theme') {
      $info['regions']['test_region'] = 'Test region';
    }
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules): void {
    // Record the ordered list of modules that were passed in to this hook so we
    // can check that the modules were enabled in the correct sequence.
    \Drupal::state()->set('module_test.install_order', $modules);
  }

  /**
   * Implements hook_modules_uninstalled().
   */
  #[Hook('modules_uninstalled')]
  public function modulesUninstalled($modules): void {
    // Record the ordered list of modules that were passed in to this hook so we
    // can check that the modules were uninstalled in the correct sequence.
    \Drupal::state()->set('module_test.uninstall_order', $modules);
  }

}
