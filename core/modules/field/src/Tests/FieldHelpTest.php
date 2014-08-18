<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldHelpTest.
 */

namespace Drupal\field\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests help display for the Field module.
 *
 * @group field
 */
class FieldHelpTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array.
   */
  public static $modules = array('field', 'help');

  // Tests field help implementation without optional core modules enabled.
  protected $profile = 'minimal';

  /**
   * The admin user that will be created.
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create the admin user.
    $this->adminUser = $this->drupalCreateUser(array('access administration pages', 'view the administration theme'));
  }

  /**
   * Test the Field module's help page.
   */
  public function testFieldHelp() {
    // Login the admin user.
    $this->drupalLogin($this->adminUser);

    // Visit the Help page and make sure no warnings or notices are thrown.
    $this->drupalGet('admin/help/field');

    // Enable the Options, Email and Field API Test modules.
    \Drupal::moduleHandler()->install(array('options', 'field_test'));
    $this->resetAll();
    \Drupal::service('plugin.manager.field.widget')->clearCachedDefinitions();
    \Drupal::service('plugin.manager.field.field_type')->clearCachedDefinitions();

    $this->drupalGet('admin/help/field');
    $this->assertLink('Options', 0, 'Options module is listed on the Field help page.');
    $this->assertText('Field API Test', 'Modules with field types that do not implement hook_help are listed.');
    $this->assertNoLink('Field API Test', 'Modules with field types that do not implement hook_help are not linked.');
    $this->assertNoLink('Link', 'Modules that have not been installed, are not listed.');
  }

}
