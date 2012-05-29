<?php

/**
 * @file
 * Definition of Drupal\help\Tests\NoHelpTest.
 */

namespace Drupal\help\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests a module without help to verify it is not listed in the help page.
 */
class NoHelpTest extends WebTestBase {
  /**
   * The user who will be created.
   */
  protected $big_user;

  public static function getInfo() {
    return array(
      'name' => 'No help',
      'description' => 'Verify no help is displayed for modules not providing any help.',
      'group' => 'Help',
    );
  }

  function setUp() {
    // Use one of the test modules that do not implement hook_help().
    parent::setUp('menu_test');
    $this->big_user = $this->drupalCreateUser(array('access administration pages'));
  }

  /**
   * Ensures modules not implementing help do not appear on admin/help.
   */
  function testMainPageNoHelp() {
    $this->drupalLogin($this->big_user);

    $this->drupalGet('admin/help');
    $this->assertNoText('Hook menu tests', t('Making sure the test module menu_test does not display a help link in admin/help'));
  }
}
