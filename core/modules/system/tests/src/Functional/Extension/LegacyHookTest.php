<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Extension;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the LegacyHook attribute.
 */
#[Group('Hook')]
#[RunTestsInSeparateProcesses]
class LegacyHookTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['legacy_hook_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the LegacyHook attribute.
   */
  #[IgnoreDeprecations]
  public function testLegacyHook(): void {
    $this->expectUserDeprecationMessage('Code in legacy_hook_test.module is deprecated in drupal:11.5.0 and must be converted before drupal:13.0.0. Use classes instead. See https://www.drupal.org/node/3619765');
    // Calling legacy_hook_test1 leads to a fatal error so there's no need
    // for asserts to show it does not get called.
    \Drupal::moduleHandler()->invokeAll('test1');
    // Verify the module actually exists and works even with one LegacyHook.
    $result = \Drupal::moduleHandler()->invokeAll('test2');
    $this->assertSame(['ok'], $result);
    \Drupal::moduleHandler()->invoke('legacy_hook_test', 'test1');
    $result = \Drupal::moduleHandler()->invoke('legacy_hook_test', 'test2');
    $this->assertSame('ok', $result);
  }

}
