<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Session;

use Drupal\Tests\BrowserTestBase;

/**
 * Drupal legacy session handling tests.
 *
 * @group legacy
 * @group Session
 */
class LegacySessionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['session_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests data persistence via the session_test module callbacks.
   */
  public function testLegacyDataPersistence(): void {
    $this->expectDeprecation('Storing values directly in $_SESSION is deprecated in drupal:11.2.0 and will become unsupported in drupal:12.0.0. Use $request-&gt;getSession()-&gt;set() instead. Affected keys: legacy_test_value. See https://www.drupal.org/node/3518527');
    $value = $this->randomMachineName();

    // Verify that the session value is stored.
    $this->drupalGet('session-test/legacy-set/' . $value);
    $this->assertSession()->pageTextContains($value);

    // Verify that the session correctly returned the stored data for an
    // authenticated user.
    $this->drupalGet('session-test/legacy-get');
    $this->assertSession()->pageTextContains($value);
  }

}
