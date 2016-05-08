<?php

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

  protected function setUp() {
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
    $this->assertText('Module overviews are provided by modules');
    $this->assertFalse(\Drupal::moduleHandler()->implementsHook('menu_test', 'help'), 'The menu_test module does not implement hook_help');
    $this->assertNoText(\Drupal::moduleHandler()->getName('menu_test'), 'Making sure the test module menu_test does not display a help link on admin/help.');

    $this->drupalGet('admin/help/menu_test');
    $this->assertResponse(404, 'Getting a module overview help page for a module that does not implement hook_help() results in a 404.');
  }

}
