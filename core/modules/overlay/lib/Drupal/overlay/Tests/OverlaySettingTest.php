<?php

/**
 * @file
 * Contains \Drupal\overlay\Tests\OverlaySettingTest.
 */

namespace Drupal\overlay\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the overlay settings.
 */
class OverlaySettingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('overlay');

  public static function getInfo() {
    return array(
      'name' => 'Overlay settings',
      'description' => 'Test that users can configure the overlay',
      'group' => 'Overlay',
    );
  }

  /**
   * Test that users can configure the overlay.
   */
  function testNodeCreation() {
    $user = $this->drupalCreateUser(array('access overlay'));
    $this->drupalLogin($user);

    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->assertFieldChecked('edit-overlay');
    $this->drupalPostForm(NULL, array('overlay' => FALSE), t('Save'));
    $this->assertNoFieldChecked('edit-overlay');
    $this->assertFalse(\Drupal::service('user.data')->get('overlay', $user->id(), 'enabled'), 'Overlay disabled');

  }
}
