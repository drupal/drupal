<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests installing the Testing profile with update notifications on.
 *
 * @group Installer
 */
class TestingProfileHooksTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_hooks';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test hooks are picked up.
   */
  public function testHookPickup(): void {
    $this->assertFalse(isset($GLOBALS['profile_procedural']));
    $this->assertFalse(isset($GLOBALS['profile_oop']));
    drupal_flush_all_caches();
    $this->assertTrue(isset($GLOBALS['profile_procedural']));
    $this->assertTrue(isset($GLOBALS['profile_oop']));
  }

}
