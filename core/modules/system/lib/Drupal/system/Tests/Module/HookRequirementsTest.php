<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\HookRequirementsTest.
 */

namespace Drupal\system\Tests\Module;

/**
 * Tests failure of hook_requirements('install').
 */
class HookRequirementsTest extends ModuleTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Requirements hook failure',
      'description' => "Attempts enabling a module that fails hook_requirements('install').",
      'group' => 'Module',
    );
  }

  /**
   * Assert that a module cannot be installed if it fails hook_requirements().
   */
  function testHookRequirementsFailure() {
    $this->assertModules(array('requirements1_test'), FALSE);

    // Attempt to install the requirements1_test module.
    $edit = array();
    $edit['modules[Testing][requirements1_test][enable]'] = 'requirements1_test';
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));

    // Makes sure the module was NOT installed.
    $this->assertText(t('Requirements 1 Test failed requirements'), 'Modules status has been updated.');
    $this->assertModules(array('requirements1_test'), FALSE);
  }
}
