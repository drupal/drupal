<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\StatusTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\system\SystemRequirements;

/**
 * Tests output on the status overview page.
 *
 * @group system
 */
class StatusTest extends WebTestBase {

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
   * Tests that the status page returns.
   */
  public function testStatusPage() {
    // Go to Administration.
    $this->drupalGet('admin/reports/status');
    $this->assertResponse(200, 'The status page is reachable.');

    $phpversion = phpversion();
    $this->assertText($phpversion, 'Php version is shown on the page.');

    // Checks if the suggestion to update to php 5.5.21 or 5.6.5 for disabling
    // multiple statements is present when necessary.
    if (\Drupal::database()->driver() === 'mysql' && !SystemRequirements::phpVersionWithPdoDisallowMultipleStatements($phpversion)) {
      $this->assertText(t('PHP (multiple statement disabling)'));
    }
    else {
      $this->assertNoText(t('PHP (multiple statement disabling)'));
    }

    if (function_exists('phpinfo')) {
      $this->assertLinkByHref(Url::fromRoute('system.php')->toString());
    }
    else {
      $this->assertNoLinkByHref(Url::fromRoute('system.php')->toString());
    }

    $this->drupalGet('admin/reports/status/php');
    $this->assertResponse(200, 'The phpinfo page is reachable.');
  }

}
