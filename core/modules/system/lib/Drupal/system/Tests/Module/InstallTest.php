<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\InstallTest.
 */

namespace Drupal\system\Tests\Module;

use Drupal\simpletest\WebTestBase;

/**
 * Unit tests for module installation.
 */
class InstallTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Module installation',
      'description' => 'Tests the installation of modules.',
      'group' => 'Module',
    );
  }

  function setUp() {
    parent::setUp('module_test');
  }

  /**
   * Test that calls to drupal_write_record() work during module installation.
   *
   * This is a useful function to test because modules often use it to insert
   * initial data in their database tables when they are being installed or
   * enabled. Furthermore, drupal_write_record() relies on the module schema
   * information being available, so this also checks that the data from one of
   * the module's hook implementations, in particular hook_schema(), is
   * properly available during this time. Therefore, this test helps ensure
   * that modules are fully functional while Drupal is installing and enabling
   * them.
   */
  function testDrupalWriteRecord() {
    // Check for data that was inserted using drupal_write_record() while the
    // 'module_test' module was being installed and enabled.
    $data = db_query("SELECT data FROM {module_test}")->fetchCol();
    $this->assertTrue(in_array('Data inserted in hook_install()', $data), t('Data inserted using drupal_write_record() in hook_install() is correctly saved.'));
    $this->assertTrue(in_array('Data inserted in hook_enable()', $data), t('Data inserted using drupal_write_record() in hook_enable() is correctly saved.'));
  }
}
