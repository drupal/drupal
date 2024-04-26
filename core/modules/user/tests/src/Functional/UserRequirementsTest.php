<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the requirements checks of the User module.
 *
 * @group user
 */
class UserRequirementsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the requirements check can detect a missing anonymous user.
   */
  public function testAnonymousUser(): void {
    // Remove the anonymous user.
    \Drupal::database()
      ->delete('users')
      ->condition('uid', 0)
      ->execute();

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
    ]));
    $this->drupalGet('/admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("The anonymous user does not exist.");
  }

}
