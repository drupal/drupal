<?php

namespace Drupal\system\Tests\Update;

use Drupal\simpletest\WebTestBase;

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
  private $updateUrl;

  /**
   * A user account with upgrade permission.
   *
   * @var \Drupal\user\UserInterface
   */
  private $updateUser;

  protected function setUp() {
    parent::setUp();
    require_once \Drupal::root() . '/core/includes/update.inc';

    $this->updateUrl = $GLOBALS['base_url'] . '/update.php';
    $this->updateUser = $this->drupalCreateUser(array('administer software updates'));
  }

  function testInvalidUpdateHook() {
    // Confirm that a module with hook_update_8000() cannot be updated.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl);
    $this->clickLink(t('Continue'));
    $this->assertText(t('Some of the pending updates cannot be applied because their dependencies were not met.'));
  }

}
