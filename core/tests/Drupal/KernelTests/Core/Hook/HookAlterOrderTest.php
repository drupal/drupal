<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\aaa_hook_order_test\Hook\AAlterHooks;
use Drupal\aaa_hook_order_test\Hook\ACrossHookReorderAlter;
use Drupal\aaa_hook_order_test\Hook\AMissingTargetAlter;
use Drupal\bbb_hook_order_test\Hook\BAlterHooks;
use Drupal\bbb_hook_order_test\Hook\BCrossHookReorderAlter;
use Drupal\bbb_hook_order_test\Hook\BMissingTargetAlter;
use Drupal\ccc_hook_order_test\Hook\CAlterHooks;
use Drupal\ddd_hook_order_test\Hook\DAlterHooks;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Hook Alter Order.
 *
 *  Tests using the 'procedural_alter' and 'procedural_subtype_alter' which
 *  are procedural only
 */
#[Group('Hook')]
#[RunTestsInSeparateProcesses]
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
   * Test ordering alter calls.
   */
  public function testAlterOrder(): void {
    // The default ordering of test_alter hooks.
    $this->assertAlterCallOrder([
      CAlterHooks::class . '::testAlter',
      AAlterHooks::class . '::testAlterAfterC',
      DAlterHooks::class . '::testAlter',
    ], 'test');

    // The default ordering of test_subtype_alter hooks.
    $this->assertAlterCallOrder([
      AAlterHooks::class . '::testSubtypeAlter',
      BAlterHooks::class . '::testSubtypeAlter',
      CAlterHooks::class . '::testSubtypeAlter',
      DAlterHooks::class . '::testSubtypeAlter',
    ], 'test_subtype');

    // Test ordering of both test_alter and test_subtype_alter hooks.
    $this->assertAlterCallOrder([
      AAlterHooks::class . '::testSubtypeAlter',
      BAlterHooks::class . '::testSubtypeAlter',
      CAlterHooks::class . '::testAlter',
      CAlterHooks::class . '::testSubtypeAlter',
      AAlterHooks::class . '::testAlterAfterC',
      DAlterHooks::class . '::testAlter',
      DAlterHooks::class . '::testSubtypeAlter',
    ], ['test', 'test_subtype']);

    $this->disableModules(['bbb_hook_order_test']);

    // Confirm that hooks from bbb_hook_order_test are absent.
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
   * Tests #[ReorderHook] targeting other hooks.
   */
  public function testReorderCrossHookAlter(): void {
    $this->assertAlterCallOrder(
      [
        // This method is reordered last only in combination with subtype hook.
        ACrossHookReorderAlter::class . '::baseAlterLastIfSubtype',
        // Implementations that are not reordered appear in order of discovery.
        ACrossHookReorderAlter::class . '::baseAlter',
        BCrossHookReorderAlter::class . '::baseAlter',
        // Ordering rules from #[Hook(.., order: ..)] are applied first, in
        // order of discovery.
        ACrossHookReorderAlter::class . '::baseAlterLast',
        ACrossHookReorderAlter::class . '::baseAlterLastAlsoIfSubtype',
        // Ordering rules from #[ReorderHook(..)] are applied last.
        BCrossHookReorderAlter::class . '::baseAlterLast',
      ],
      'test_cross_hook_reorder_base',
    );
    $this->assertAlterCallOrder(
      [
        // This method is reordered last only in combination with base hook.
        ACrossHookReorderAlter::class . '::subtypeAlterLastIfBaseHook',
        // Implementations that are not reordered appear in order of discovery.
        ACrossHookReorderAlter::class . '::subtypeAlter',
        BCrossHookReorderAlter::class . '::subtypeAlter',
        // This implementation has #[Hook(.., order: Order::Last)].
        ACrossHookReorderAlter::class . '::subtypeAlterLast',
      ],
      'test_cross_hook_reorder_subtype',
    );
    $this->assertAlterCallOrder(
      [
        // Implementations that are not reordered appear in order of modules,
        // then order of hooks passed to ->alter(), then order of discovery.
        // We remove ReorderHook directives when the identifier and hook
        // targeted combination does not exist.

        // This method has a reorder targeting it, but it is using a hook that
        // does not point to this method so the reorder directive is dropped.
        ACrossHookReorderAlter::class . '::baseAlterLastIfSubtype',

        ACrossHookReorderAlter::class . '::baseAlter',

        // This method has a reorder targeting it, but it is using a hook that
        // does not point to this method so the reorder directive is dropped.
        ACrossHookReorderAlter::class . '::subtypeAlterLastIfBaseHook',
        ACrossHookReorderAlter::class . '::subtypeAlter',
        // These two methods appear in opposite order in the class, but appear
        // swapped, because one is for the base alter hook, the other for the
        // subtype alter hook.
        BCrossHookReorderAlter::class . '::baseAlter',
        BCrossHookReorderAlter::class . '::subtypeAlter',
        // Ordering rules for the base hook are applied first.
        // At first those from #[Hook('..base..', order: ..)].
        ACrossHookReorderAlter::class . '::baseAlterLast',

        // This method has a reorder targeting it, but it is using a hook that
        // does not point to this method so the reorder directive is dropped.
        ACrossHookReorderAlter::class . '::baseAlterLastAlsoIfSubtype',
        BCrossHookReorderAlter::class . '::baseAlterLast',
        // Ordering rules for the subtype hook are applied last.
        // At first those from #[Hook('..subtype..', order: ..)].
        ACrossHookReorderAlter::class . '::subtypeAlterLast',
      ],
      ['test_cross_hook_reorder_base', 'test_cross_hook_reorder_subtype'],
    );
  }

  /**
   * Tests #[ReorderHook] attributes with missing target.
   *
   * There are different kinds of missing target:
   *   - The target method to be reordered or removed may not exist.
   *   - The hook being targeted may have no implementations.
   *   - The target method exists, but it is registered to a different hook.
   *
   * The expected behavior in these cases is that the reorder or remove
   * attribute should have no effect, and especially not cause any errors.
   *
   * However, for alter hooks, the last case is a bit special.
   *
   * @see \Drupal\KernelTests\Core\Hook\HookOrderTest::testReorderMissingTarget()
   * @see \Drupal\xyz_hook_order_test\Hook\XyzMissingTargetAlter
   */
  public function testReorderAlterMissingTarget(): void {
    // At the beginning, the xyz_hook_order_test is not installed, so no
    // reordering is applied.
    // This verifies that all implementations for this test are correctly
    // declared and discovered.
    $this->assertAlterCallOrder(
      [
        AMissingTargetAlter::class . '::testABAlter',
        BMissingTargetAlter::class . '::testABAlterReorderedFirstByXyz',
        BMissingTargetAlter::class . '::testABAlterRemovedByXyz',
      ],
      'test_ab',
    );
    $this->assertAlterCallOrder(
      [
        BMissingTargetAlter::class . '::testBAlter',
        BMissingTargetAlter::class . '::testBAlterReorderedFirstByXyz',
        BMissingTargetAlter::class . '::testBAlterRemovedByXyz',
      ],
      'test_b',
    );
    $this->assertAlterCallOrder(
      [
        AMissingTargetAlter::class . '::testASupertypeAlter',
        AMissingTargetAlter::class . '::testASupertypeAlterReorderedFirstForBSubtypeByXyz',
        AMissingTargetAlter::class . '::testASupertypeAlterRemovedForBSubtypeByXyz',
      ],
      'test_a_supertype',
    );
    $this->assertAlterCallOrder(
      [
        BMissingTargetAlter::class . '::testBSubtypeAlter',
      ],
      'test_b_subtype',
    );
    $this->assertAlterCallOrder(
      [
        AMissingTargetAlter::class . '::testASupertypeAlter',
        AMissingTargetAlter::class . '::testASupertypeAlterReorderedFirstForBSubtypeByXyz',
        AMissingTargetAlter::class . '::testASupertypeAlterRemovedForBSubtypeByXyz',
        BMissingTargetAlter::class . '::testBSubtypeAlter',
      ],
      ['test_a_supertype', 'test_b_subtype'],
    );

    // Install the module that has the reorder and remove hook attributes.
    $this->enableModules(['xyz_hook_order_test']);

    $this->assertAlterCallOrder(
      [
        BMissingTargetAlter::class . '::testABAlterReorderedFirstByXyz',
        AMissingTargetAlter::class . '::testABAlter',
      ],
      'test_ab',
    );
    $this->assertAlterCallOrder(
      [
        BMissingTargetAlter::class . '::testBAlterReorderedFirstByXyz',
        BMissingTargetAlter::class . '::testBAlter',
      ],
      'test_b',
    );
    $this->assertAlterCallOrder(
      [
        AMissingTargetAlter::class . '::testASupertypeAlter',
        AMissingTargetAlter::class . '::testASupertypeAlterReorderedFirstForBSubtypeByXyz',
        AMissingTargetAlter::class . '::testASupertypeAlterRemovedForBSubtypeByXyz',
      ],
      'test_a_supertype',
    );
    $this->assertAlterCallOrder(
      [
        BMissingTargetAlter::class . '::testBSubtypeAlter',
      ],
      'test_b_subtype',
    );
    $this->assertAlterCallOrder(
      [
        AMissingTargetAlter::class . '::testASupertypeAlter',
        AMissingTargetAlter::class . '::testASupertypeAlterReorderedFirstForBSubtypeByXyz',
        AMissingTargetAlter::class . '::testASupertypeAlterRemovedForBSubtypeByXyz',
        BMissingTargetAlter::class . '::testBSubtypeAlter',
      ],
      ['test_a_supertype', 'test_b_subtype'],
    );

    // Uninstall the B module, which contains the reorder targets.
    // This originally caused a TypeError, if this test completes successfully,
    // then there is no TypeError.
    $this->disableModules(['bbb_hook_order_test']);
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
