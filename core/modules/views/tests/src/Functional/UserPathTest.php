<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests overriding user paths using wildcards.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class UserPathTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test views to use.
   *
   * @var array
   */
  public static $testViews = ['test_user_path'];

  /**
   * Tests if the login page is still available when using a wildcard path.
   */
  public function testUserLoginPage(): void {
    $this->drupalGet('user/login');
    $this->assertSession()->statusCodeEquals(200);
  }

}
