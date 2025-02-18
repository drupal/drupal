<?php

declare(strict_types=1);

namespace Drupal\system_test\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for system_test.
 */
class SystemTestHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.system_test':
        $output = '';
        $output .= '<h2>' . $this->t('Test Help Page') . '</h2>';
        $output .= '<p>' . $this->t('This is a test help page for the system_test module for the purpose of testing if the "Help" link displays properly.') . '</p>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules): void {
    if (\Drupal::state()->get('system_test.verbose_module_hooks')) {
      foreach ($modules as $module) {
        \Drupal::messenger()->addStatus($this->t('hook_modules_installed fired for @module', ['@module' => $module]));
      }
    }
  }

  /**
   * Implements hook_modules_uninstalled().
   */
  #[Hook('modules_uninstalled')]
  public function modulesUninstalled($modules, $is_syncing): void {
    if (\Drupal::state()->get('system_test.verbose_module_hooks')) {
      foreach ($modules as $module) {
        \Drupal::messenger()->addStatus($this->t('hook_modules_uninstalled fired for @module', ['@module' => $module]));
      }
    }
    // Save the config.installer isSyncing() value to state to check that it is
    // correctly set when installing module during config import.
    \Drupal::state()->set('system_test_modules_uninstalled_config_installer_syncing', \Drupal::service('config.installer')->isSyncing());
    // Save the $is_syncing parameter value to state to check that it is
    // correctly set when installing module during config import.
    \Drupal::state()->set('system_test_modules_uninstalled_syncing_param', $is_syncing);
  }

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    // We need a static otherwise the last test will fail to alter common_test.
    static $test;
    if (($dependencies = \Drupal::state()->get('system_test.dependencies')) || $test) {
      if ($file->getName() == 'module_test') {
        $info['hidden'] = FALSE;
        $info['dependencies'][] = array_shift($dependencies);
        \Drupal::state()->set('system_test.dependencies', $dependencies);
        $test = TRUE;
      }
      if ($file->getName() == 'common_test') {
        $info['hidden'] = FALSE;
        $info['version'] = '8.x-2.4-beta3';
      }
    }
    // Make the system_dependencies_test visible by default.
    if ($file->getName() == 'system_dependencies_test') {
      $info['hidden'] = FALSE;
    }
    if (in_array($file->getName(), [
      'system_incompatible_module_version_dependencies_test',
      'system_incompatible_core_version_dependencies_test',
      'system_incompatible_module_version_test',
    ])) {
      $info['hidden'] = FALSE;
    }
    if ($file->getName() == 'requirements1_test' || $file->getName() == 'requirements2_test') {
      $info['hidden'] = FALSE;
    }
    if ($file->getName() == 'system_test') {
      $info['hidden'] = \Drupal::state()->get('system_test.module_hidden', TRUE);
    }
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    // Used by FrontPageTestCase to get the results of
    // \Drupal::service('path.matcher')->isFrontPage().
    $frontpage = \Drupal::state()->get('system_test.front_page_output', 0);
    if ($frontpage && \Drupal::service('path.matcher')->isFrontPage()) {
      \Drupal::messenger()->addStatus($this->t('On front page.'));
    }
  }

  /**
   * Implements hook_filetransfer_info().
   */
  #[Hook('filetransfer_info')]
  public function filetransferInfo(): array {
    return [
      'system_test' => [
        'title' => $this->t('System Test FileTransfer'),
        'class' => 'Drupal\system_test\MockFileTransfer',
        'weight' => -10,
      ],
    ];
  }

  /**
   * Implements hook_module_preinstall().
   */
  #[Hook('module_preinstall')]
  public function modulePreinstall($module, bool $is_syncing): void {
    \Drupal::messenger()->addStatus('system_test_preinstall_module called');
    \Drupal::state()->set('system_test_preinstall_module', $module);
    // Save the config.installer isSyncing() value to state to check that it is
    // correctly set when installing module during config import.
    \Drupal::state()->set('system_test_preinstall_module_config_installer_syncing', \Drupal::service('config.installer')->isSyncing());
    // Save the $is_syncing parameter value to state to check that it is
    // correctly set when installing module during config import.
    \Drupal::state()->set('system_test_preinstall_module_syncing_param', $is_syncing);
  }

  /**
   * Implements hook_module_preuninstall().
   */
  #[Hook('module_preuninstall')]
  public function modulePreuninstall($module, bool $is_syncing): void {
    \Drupal::state()->set('system_test_preuninstall_module', $module);
    // Save the config.installer isSyncing() value to state to check that it is
    // correctly set when uninstalling module during config import.
    \Drupal::state()->set('system_test_preuninstall_module_config_installer_syncing', \Drupal::service('config.installer')->isSyncing());
    // Save the $is_syncing parameter value to state to check that it is
    // correctly set when installing module during config import.
    \Drupal::state()->set('system_test_preuninstall_module_syncing_param', $is_syncing);
  }

}
