<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests installing the Testing profile with update notifications on.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
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
  #[IgnoreDeprecations]
  public function testHookPickup(): void {
    $this->expectUserDeprecationMessage('Code in testing_hooks.profile is deprecated in drupal:11.5.0 and must be converted before drupal:13.0.0. Use classes instead. See https://www.drupal.org/node/3619765');
    $this->assertFalse(isset($GLOBALS['profile_procedural']));
    $this->assertFalse(isset($GLOBALS['profile_oop']));
    drupal_flush_all_caches();
    $this->assertTrue(isset($GLOBALS['profile_procedural']));
    $this->assertTrue(isset($GLOBALS['profile_oop']));
  }

}
