<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\InvalidUpdateHookTest.
 */

namespace Drupal\system\Tests\Update;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Extension\ExtensionSchemaVersionException;

/**
 * Tests that a module implementing hook_update_8000() causes an error to be
 * displayed on update.
 *
 * @group Update
 */
class InvalidUpdateHookTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update_test_invalid_hook', 'update_script_test', 'dblog');

  /**
   * URL for the upgrade script.
   *
   * @var string
   */
  private $update_url;

  /**
   * A user account with upgrade permission.
   *
   * @var \Drupal\user\UserInterface
   */
  private $update_user;

  protected function setUp() {
    parent::setUp();
    require_once DRUPAL_ROOT . '/core/includes/update.inc';

    $this->update_url = $GLOBALS['base_url'] . '/update.php';
    $this->update_user = $this->drupalCreateUser(array('administer software updates'));
  }

  function testInvalidUpdateHook() {
    // Confirm that a module with hook_update_8000() cannot be updated.
    $this->drupalLogin($this->update_user);
    $this->drupalGet($this->update_url);
    $this->clickLink(t('Continue'));
    $this->assertText(t('Some of the pending updates cannot be applied because their dependencies were not met.'));
  }

}
