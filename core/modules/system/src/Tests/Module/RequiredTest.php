<?php

namespace Drupal\system\Tests\Module;

/**
 * Attempt disabling of required modules.
 *
 * @group Module
 */
class RequiredTest extends ModuleTestBase {
  /**
   * Assert that core required modules cannot be disabled.
   */
  public function testDisableRequired() {
    $module_info = system_get_info('module');
    $this->drupalGet('admin/modules');
    foreach ($module_info as $module => $info) {
      // Check to make sure the checkbox for each required module is disabled
      // and checked (or absent from the page if the module is also hidden).
      if (!empty($info['required'])) {
        $field_name = 'modules[' . $module . '][enable]';
        if (empty($info['hidden'])) {
          $this->assertFieldByXPath("//input[@name='$field_name' and @disabled='disabled' and @checked='checked']", '', format_string('Field @name was disabled and checked.', ['@name' => $field_name]));
        }
        else {
          $this->assertNoFieldByName($field_name);
        }
      }
    }
  }

}
