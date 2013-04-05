<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\BareMinimalAnonymousUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests the upgrade path without prior creation of config directions.
 */
class BareMinimalAnonymousUpgradePathTest extends BareMinimalUpgradePathTest {

  public static function getInfo() {
    return array(
      'name' => 'Basic minimal profile upgrade, free access',
      'description' => 'Basic upgrade path tests for a minimal profile install with a bare database and update_free_access set to TRUE.',
      'group' => 'Upgrade path',
    );
  }

  /**
   * Overrides \Drupal\system\Tests\Upgrade\UpgradePathTestBase::setUp().
   */
  public function setUp() {
    parent::setUp();

    // Override $update_free_access in settings.php to allow the anonymous user
    // to run updates.
    $settings['settings']['update_free_access'] = (object) array(
      'value' => TRUE,
      'required' => TRUE,
    );
    $this->writeSettings($settings);
  }

  /**
   * Overrides \Drupal\system\Tests\Upgrade\UpgradePathTestBase::prepareD8Session().
   */
  protected function prepareD8Session() {
    // There is no active session, so nothing needs to be done here.
  }

  /**
   * Overrides \Drupal\system\Tests\Upgrade\UpgradePathTestBase::assertSessionKept().
   */
  protected function finishUpgradeSession() {
    // There is no active session, so nothing needs to be done here.
  }

}
