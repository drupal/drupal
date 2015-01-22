<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\TrustedHostsTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Site\Settings;
use Drupal\simpletest\WebTestBase;

/**
 * Tests output on the status overview page.
 *
 * @group system
 */
class TrustedHostsTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the status page shows an error when the trusted host setting
   * is missing from settings.php
   */
  public function testStatusPageWithoutConfiguration() {
    $this->drupalGet('admin/reports/status');
    $this->assertResponse(200, 'The status page is reachable.');

    $this->assertRaw(t('Trusted Host Settings'));
    $this->assertRaw(t('The trusted_host_patterns setting is not configured in settings.php.'));
  }

  /**
   * Tests that the status page shows the trusted patterns from settings.php.
   */
  public function testStatusPageWithConfiguration() {
    $settings['settings']['trusted_host_patterns'] = (object) array(
      'value' => array('^' . preg_quote(\Drupal::request()->getHost()) . '$'),
      'required' => TRUE,
    );

    $this->writeSettings($settings);

    $this->drupalGet('admin/reports/status');
    $this->assertResponse(200, 'The status page is reachable.');

    $this->assertRaw(t('Trusted Host Settings'));
    $this->assertRaw(t('The trusted_host_patterns setting is set to allow'));
  }

}
