<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Update\UpdatesWith7x.
 */

namespace Drupal\system\Tests\Update;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for missing update dependencies.
 */
class UpdatesWith7x extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update_test_with_7x');

  /**
   * The URL for the update page.
   */
  private $update_url;

  /**
   * An administrative user.
   */
  private $update_user;

  public static function getInfo() {
    return array(
      'name' => '7.x update hooks',
      'description' => 'Tests that the minimum schema version is correct even if only 7.x update hooks are retained .',
      'group' => 'Update API',
    );
  }

  function setUp() {
    parent::setUp();
    require_once DRUPAL_ROOT . '/core/includes/update.inc';
    $this->update_url = $GLOBALS['base_url'] . '/core/update.php';
    $this->update_user = $this->drupalCreateUser(array('administer software updates'));
  }

  function testWith7x() {
    // Ensure that the minimum schema version is 8000, despite 7200 update
    // hooks and a 7XXX hook_update_last_removed().
    $this->assertEqual(drupal_get_installed_schema_version('update_test_with_7x'), 8000);

    // Try to manually set the schema version to 7110 and ensure that no
    // updates are allowed.
    drupal_set_installed_schema_version('update_test_with_7x', 7110);

    // Click through update.php with 'administer software updates' permission.
    $this->drupalLogin($this->update_user);
    $this->drupalPostForm($this->update_url, array(), t('Continue'), array('external' => TRUE));
    $this->assertText(t('Some of the pending updates cannot be applied because their dependencies were not met.'));
  }
}
