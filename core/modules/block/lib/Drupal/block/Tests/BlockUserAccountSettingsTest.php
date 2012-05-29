<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockUserAccountSettingsTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests personalized block settings for user accounts.
 */
class BlockUserAccountSettingsTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Personalized block settings',
      'description' => 'Tests the block settings in user accounts.',
      'group' => 'Block',
    );
  }

  public function setUp() {
    parent::setUp(array('block', 'field_ui'));
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the personalized block is shown.
   */
  function testAccountSettingsPage() {
    $this->drupalGet('admin/config/people/accounts/fields');
    $this->assertText(t('Personalize blocks'), 'Personalized block is present.');
  }
}
