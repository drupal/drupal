<?php

declare(strict_types=1);

namespace Drupal\TestSite;

use Drupal\Core\Extension\ModuleInstallerInterface;

/**
 * Setup file used by core/tests/Drupal/Nightwatch/Tests/htmx/htmxTest.js.
 */
class HtmxAssetLoadTestSetup implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    // Install required modules.
    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['test_htmx']);
  }

}
