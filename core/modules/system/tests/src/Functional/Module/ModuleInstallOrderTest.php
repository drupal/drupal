<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Module;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Enable module without dependency enabled.
 */
#[Group('Module')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class ModuleInstallOrderTest extends ModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that module dependencies are installed correctly in the UI.
   *
   * Dependencies should be installed before their dependents.
   */
  public function testModuleInstallOrder(): void {
    \Drupal::service('module_installer')->install(['module_test'], FALSE);
    $this->resetAll();
    $this->assertModules(['module_test'], TRUE);
    \Drupal::state()->set('module_test.dependency', 'dependency');
    // module_test creates a dependency chain: dblog depends on config which
    // depends on help.
    $expected_order = ['help', 'config', 'dblog'];

    // Enable the modules through the UI, verifying that the dependency chain
    // is correct.
    $edit = [];
    $edit['modules[dblog][enable]'] = 'dblog';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertModules(['dblog'], FALSE);
    // Note that dependencies are sorted alphabetically in the confirmation
    // message.
    $this->assertSession()->pageTextContains('You must install the following modules to install Database Logging:Configuration ManagerHelp');

    $this->drupalGet('admin/modules');
    $edit['modules[config][enable]'] = 'config';
    $edit['modules[help][enable]'] = 'help';
    $this->submitForm($edit, 'Install');
    $this->assertModules(['dblog', 'config', 'help'], TRUE);

    // Check the actual order which is saved by module_test_modules_enabled().
    $module_order = \Drupal::state()->get('module_test.install_order', []);
    $this->assertSame($expected_order, $module_order);
  }

}
