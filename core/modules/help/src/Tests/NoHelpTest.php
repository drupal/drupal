<?php

/**
 * @file
 * Definition of Drupal\help\Tests\NoHelpTest.
 */

namespace Drupal\help\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Verify no help is displayed for modules not providing any help.
 *
 * @group help
 */
class NoHelpTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * Use one of the test modules that do not implement hook_help().
   *
   * @var array.
   */
  public static $modules = array('help', 'menu_test');

  /**
   * The user who will be created.
   */
  protected $adminUser;

  public function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array('access administration pages'));
  }

  /**
   * Ensures modules not implementing help do not appear on admin/help.
   */
  public function testMainPageNoHelp() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/help');
    $this->assertResponse(200);
    $this->assertText('Help is available on the following items', 'Help page is found.');
    $this->assertNoText('Hook menu tests', 'Making sure the test module menu_test does not display a help link on admin/help.');
  }
}
