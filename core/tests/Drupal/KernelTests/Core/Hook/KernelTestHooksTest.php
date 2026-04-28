<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that hook implementations in Kernel test classes are executed.
 */
#[Group('Hook')]
#[RunTestsInSeparateProcesses]
class KernelTestHooksTest extends KernelTestBase {

  /**
   * Invoke hooks and alter hooks and confirm implementations are executed.
   */
  public function testHookMethodExecution(): void {
    // Invoke a hook and confirm hookTestHook() executed.
    $values = \Drupal::moduleHandler()->invokeAll('test_hook');
    $this->assertSame([static::class . '::hookTestHook'], $values);

    // Invoke alter hooks, with multiple types of related hooks and confirm that
    // both hookTestHookAlter() and hookTestVariantHookAlter() executed.
    $alterHooks = ['test_hook', 'test_variant_hook'];
    \Drupal::moduleHandler()->alter($alterHooks, $values);
    $this->assertSame([
      static::class . '::hookTestHook',
      static::class . '::hookTestHookAlter',
      static::class . '::hookTestVariantHookAlter',
    ], $values);
  }

  /**
   * Implements hook_test_hook().
   */
  #[Hook('test_hook')]
  public function hookTestHook(): string {
    return __METHOD__;
  }

  /**
   * Implements hook_test_hook_alter().
   */
  #[Hook('test_hook_alter')]
  public function hookTestHookAlter(array &$values): void {
    $values[] = __METHOD__;
  }

  /**
   * Implements hook_test_variant_hook_alter().
   */
  #[Hook('test_variant_hook_alter')]
  public function hookTestVariantHookAlter(array &$values): void {
    $values[] = __METHOD__;
  }

}
