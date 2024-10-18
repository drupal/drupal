<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Extension;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the LegacyHook attribute.
 *
 * @group Hook
 */
class LegacyHookTest extends BrowserTestBase {

  protected static $modules = ['legacy_hook_test'];

  protected $defaultTheme = 'stark';

  public function testLegacyHook(): void {
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
