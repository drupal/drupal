<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Module;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Attempts enabling a module that fails hook_requirements('install').
 */
#[Group('Module')]
#[RunTestsInSeparateProcesses]
class HookRequirementsTest extends ModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Assert that a module cannot be installed if it fails hook_requirements().
   */
  public function testHookRequirementsFailure(): void {
    $this->assertModules(['requirements1_test'], FALSE);

    // Attempt to install the requirements1_test module.
    $edit = [];
    $edit['modules[requirements1_test][enable]'] = 'requirements1_test';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    // Makes sure the module was NOT installed.
    $this->assertSession()->pageTextContains('Requirements 1 Test failed requirements');
    $this->assertModules(['requirements1_test'], FALSE);
  }

}
