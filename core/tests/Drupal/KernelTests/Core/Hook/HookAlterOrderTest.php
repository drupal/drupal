<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\aaa_hook_order_test\Hook\AAlterHooks;
use Drupal\aaa_hook_order_test\Hook\ModuleImplementsAlter;
use Drupal\bbb_hook_order_test\Hook\BAlterHooks;
use Drupal\ccc_hook_order_test\Hook\CAlterHooks;
use Drupal\ddd_hook_order_test\Hook\DAlterHooks;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Hook
 * @group legacy
 */
class HookAlterOrderTest extends KernelTestBase {

  use HookOrderTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'aaa_hook_order_test',
    'bbb_hook_order_test',
    'ccc_hook_order_test',
    'ddd_hook_order_test',
  ];

  /**
   * Tests procedural implementations of module implements alter ordering.
   */
  public function testProceduralModuleImplementsAlterOrder(): void {
    $this->assertAlterCallOrder($main_unaltered = [
      'aaa_hook_order_test_procedural_alter',
      'bbb_hook_order_test_procedural_alter',
      'ccc_hook_order_test_procedural_alter',
    ], 'procedural');

    $this->assertAlterCallOrder($sub_unaltered = [
      'aaa_hook_order_test_procedural_subtype_alter',
      'bbb_hook_order_test_procedural_subtype_alter',
      'ccc_hook_order_test_procedural_subtype_alter',
    ], 'procedural_subtype');

    $this->assertAlterCallOrder($combined_unaltered = [
      'aaa_hook_order_test_procedural_alter',
      'aaa_hook_order_test_procedural_subtype_alter',
      'bbb_hook_order_test_procedural_alter',
      'bbb_hook_order_test_procedural_subtype_alter',
      'ccc_hook_order_test_procedural_alter',
      'ccc_hook_order_test_procedural_subtype_alter',
    ], ['procedural', 'procedural_subtype']);

    $move_b_down = function (array &$implementations): void {
      // Move B to the end, no matter which hook.
      $group = $implementations['bbb_hook_order_test'];
      unset($implementations['bbb_hook_order_test']);
      $implementations['bbb_hook_order_test'] = $group;
    };
    $modules = ['aaa_hook_order_test', 'bbb_hook_order_test', 'ccc_hook_order_test'];

    // Test with module B moved to the end for both hooks.
    ModuleImplementsAlter::set(
      function (array &$implementations, string $hook) use ($modules, $move_b_down): void {
        if (!in_array($hook, ['procedural_alter', 'procedural_subtype_alter'])) {
          return;
        }
        $this->assertSame($modules, array_keys($implementations));
        $move_b_down($implementations);
      },
    );
    \Drupal::service('kernel')->rebuildContainer();

    $this->assertAlterCallOrder($main_altered = [
      'aaa_hook_order_test_procedural_alter',
      'ccc_hook_order_test_procedural_alter',
      // The implementation of B has been moved.
      'bbb_hook_order_test_procedural_alter',
    ], 'procedural');

    $this->assertAlterCallOrder($sub_altered = [
      'aaa_hook_order_test_procedural_subtype_alter',
      'ccc_hook_order_test_procedural_subtype_alter',
      // The implementation of B has been moved.
      'bbb_hook_order_test_procedural_subtype_alter',
    ], 'procedural_subtype');

    $this->assertAlterCallOrder($combined_altered = [
      'aaa_hook_order_test_procedural_alter',
      'aaa_hook_order_test_procedural_subtype_alter',
      'ccc_hook_order_test_procedural_alter',
      'ccc_hook_order_test_procedural_subtype_alter',
      // The implementation of B has been moved.
      'bbb_hook_order_test_procedural_alter',
      'bbb_hook_order_test_procedural_subtype_alter',
    ], ['procedural', 'procedural_subtype']);

    // If the altered hook is not the first one, implementations are back in
    // their unaltered order.
    $this->assertAlterCallOrder($main_unaltered, ['other_main_type', 'procedural']);
    $this->assertAlterCallOrder($sub_unaltered, ['other_main_type', 'procedural_subtype']);
    $this->assertAlterCallOrder($combined_unaltered, ['other_main_type', 'procedural', 'procedural_subtype']);

    // Test with module B moved to the end for the main hook.
    ModuleImplementsAlter::set(
      function (array &$implementations, string $hook) use ($modules, $move_b_down): void {
        if (!in_array($hook, ['procedural_alter', 'procedural_subtype_alter'])) {
          return;
        }
        $this->assertSame($modules, array_keys($implementations));
        if ($hook !== 'procedural_alter') {
          return;
        }
        $move_b_down($implementations);
      },
    );
    \Drupal::service('kernel')->rebuildContainer();

    $this->assertAlterCallOrder($main_altered, 'procedural');
    $this->assertAlterCallOrder($sub_unaltered, 'procedural_subtype');
    $this->assertAlterCallOrder($combined_altered, ['procedural', 'procedural_subtype']);

    // Test with module B moved to the end for the subtype hook.
    ModuleImplementsAlter::set(
      function (array &$implementations, string $hook) use ($modules, $move_b_down): void {
        if (!in_array($hook, ['procedural_alter', 'procedural_subtype_alter'])) {
          return;
        }
        $this->assertSameCallList($modules, array_keys($implementations));
        if ($hook !== 'procedural_subtype_alter') {
          return;
        }
        $move_b_down($implementations);
      },
    );
    \Drupal::service('kernel')->rebuildContainer();

    $this->assertAlterCallOrder($main_unaltered, 'procedural');
    $this->assertAlterCallOrder($sub_altered, 'procedural_subtype');
    $this->assertAlterCallOrder($combined_unaltered, ['procedural', 'procedural_subtype']);
  }

  /**
   * Test ordering alter calls.
   */
  public function testAlterOrder(): void {
    $this->assertAlterCallOrder([
      CAlterHooks::class . '::testAlter',
      AAlterHooks::class . '::testAlterAfterC',
      DAlterHooks::class . '::testAlter',
    ], 'test');

    $this->assertAlterCallOrder([
      AAlterHooks::class . '::testSubtypeAlter',
      BAlterHooks::class . '::testSubtypeAlter',
      CAlterHooks::class . '::testSubtypeAlter',
      DAlterHooks::class . '::testSubtypeAlter',
    ], 'test_subtype');

    $this->assertAlterCallOrder([
      // The implementation from 'D' is gone.
      AAlterHooks::class . '::testSubtypeAlter',
      BAlterHooks::class . '::testSubtypeAlter',
      CAlterHooks::class . '::testAlter',
      CAlterHooks::class . '::testSubtypeAlter',
      AAlterHooks::class . '::testAlterAfterC',
      DAlterHooks::class . '::testAlter',
      DAlterHooks::class . '::testSubtypeAlter',
    ], ['test', 'test_subtype']);

    $this->disableModules(['bbb_hook_order_test']);

    $this->assertAlterCallOrder([
      CAlterHooks::class . '::testAlter',
      AAlterHooks::class . '::testAlterAfterC',
      DAlterHooks::class . '::testAlter',
    ], 'test');

    $this->assertAlterCallOrder([
      AAlterHooks::class . '::testSubtypeAlter',
      CAlterHooks::class . '::testSubtypeAlter',
      DAlterHooks::class . '::testSubtypeAlter',
    ], 'test_subtype');

    $this->assertAlterCallOrder([
      AAlterHooks::class . '::testSubtypeAlter',
      CAlterHooks::class . '::testAlter',
      CAlterHooks::class . '::testSubtypeAlter',
      AAlterHooks::class . '::testAlterAfterC',
      DAlterHooks::class . '::testAlter',
      DAlterHooks::class . '::testSubtypeAlter',
    ], ['test', 'test_subtype']);
  }

  /**
   * Asserts the call order from an alter call.
   *
   * Also asserts additional $type argument values that are meant to produce the
   * same result.
   *
   * @param list<string> $expected
   *   Expected call list, as strings from __METHOD__ or __FUNCTION__.
   * @param string|list<string> $type
   *   First argument to pass to ->alter().
   */
  protected function assertAlterCallOrder(array $expected, string|array $type): void {
    $this->assertSameCallList(
      $expected,
      $this->alter($type),
    );
  }

  /**
   * Invokes ModuleHandler->alter() and returns the altered array.
   *
   * @param string|list<string> $type
   *   Alter type or list of alter types.
   *
   * @return array
   *   The altered array.
   */
  protected function alter(string|array $type): array {
    $data = [];
    \Drupal::moduleHandler()->alter($type, $data);
    return $data;
  }

}
