<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\aaa_hook_order_test\Hook\AHooks;
use Drupal\aaa_hook_order_test\Hook\AMissingTargetHooks;
use Drupal\bbb_hook_order_test\Hook\BHooks;
use Drupal\bbb_hook_order_test\Hook\BMissingTargetHooks;
use Drupal\ccc_hook_order_test\Hook\CHooks;
use Drupal\ddd_hook_order_test\Hook\DHooks;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Hook Order.
 */
#[Group('Hook')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class HookOrderTest extends KernelTestBase {

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
   * Test hook implementation order.
   */
  public function testHookOrder(): void {
    $this->assertSameCallList(
      [
        CHooks::class . '::testHookReorderFirst',
        CHooks::class . '::testHookFirst',
        AHooks::class . '::testHookFirst',
        'aaa_hook_order_test_test_hook',
        AHooks::class . '::testHook',
        'bbb_hook_order_test_test_hook',
        BHooks::class . '::testHook',
        AHooks::class . '::testHookAfterB',
        'ccc_hook_order_test_test_hook',
        CHooks::class . '::testHook',
        'ddd_hook_order_test_test_hook',
        DHooks::class . '::testHook',
        AHooks::class . '::testHookLast',
      ],
      \Drupal::moduleHandler()->invokeAll('test_hook'),
    );
  }

  /**
   * Tests #[ReorderHook] attributes with missing target.
   *
   * There are different kinds of missing target:
   *   - The target method to be reordered may not exist.
   *   - The hook being targeted may have no implementations.
   *   - The target method exists, but it is registered to a different hook.
   *
   * The expected behavior in these cases is that the reorder or remove
   * attribute should have no effect, and especially not cause any errors.
   *
   * @see \Drupal\KernelTests\Core\Hook\HookAlterOrderTest::testReorderAlterMissingTarget()
   * @see \Drupal\xyz_hook_order_test\Hook\XyzMissingTargetHooks
   */
  public function testReorderMissingTarget(): void {
    // At the beginning, the xyz_hook_order_test is not installed, so no
    // reordering is applied.
    // This verifies that all implementations for this test are correctly
    // declared and discovered.
    $this->assertSameCallList(
      [
        AMissingTargetHooks::class . '::testABHook',
        BMissingTargetHooks::class . '::testABHookReorderedFirstByXyz',
        BMissingTargetHooks::class . '::testABHookRemovedByXyz',
      ],
      \Drupal::moduleHandler()->invokeAll('test_ab_hook'),
    );
    $this->assertSameCallList(
      [
        BMissingTargetHooks::class . '::testBHook',
        BMissingTargetHooks::class . '::testBHookReorderedFirstByXyz',
        BMissingTargetHooks::class . '::testBHookRemovedByXyz',
      ],
      \Drupal::moduleHandler()->invokeAll('test_b_hook'),
    );
    $this->assertSameCallList(
      [
        AMissingTargetHooks::class . '::testUnrelatedHookReorderedLastForHookB',
        AMissingTargetHooks::class . '::testUnrelatedHookRemovedForHookB',
        BMissingTargetHooks::class . '::testUnrelatedHook',
      ],
      \Drupal::moduleHandler()->invokeAll('test_unrelated_hook'),
    );

    // Install the module that has the reorder and remove hook attributes.
    $this->enableModules(['xyz_hook_order_test']);

    // Reorder and remove operations are applied to 'test_ab_hook'.
    $this->assertSameCallList(
      [
        BMissingTargetHooks::class . '::testABHookReorderedFirstByXyz',
        AMissingTargetHooks::class . '::testABHook',
      ],
      \Drupal::moduleHandler()->invokeAll('test_ab_hook'),
    );
    // Reorder and remove operations are applied to 'test_b_hook'.
    $this->assertSameCallList(
      [
        BMissingTargetHooks::class . '::testBHookReorderedFirstByXyz',
        BMissingTargetHooks::class . '::testBHook',
      ],
      \Drupal::moduleHandler()->invokeAll('test_b_hook'),
    );
    // No reorder or remove operations are applied to the unrelated hook,
    // even though the methods are being targeted.
    $this->assertSameCallList(
      [
        AMissingTargetHooks::class . '::testUnrelatedHookReorderedLastForHookB',
        AMissingTargetHooks::class . '::testUnrelatedHookRemovedForHookB',
        BMissingTargetHooks::class . '::testUnrelatedHook',
      ],
      \Drupal::moduleHandler()->invokeAll('test_unrelated_hook'),
    );

    // Uninstall the B module, which contains the reorder targets.
    $this->expectException(\TypeError::class);
    $old_request = \Drupal::request();
    try {
      $this->disableModules(['bbb_hook_order_test']);
    }
    finally {
      // Restore a request and session, to avoid error during tearDown().
      /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
      $request_stack = $this->container->get('request_stack');
      $request_stack->push($old_request);
    }
  }

  /**
   * Tests hook order when each module has either oop or procedural listeners.
   *
   * This would detect a possible mistake where we would first collect modules
   * from all procedural and then from all oop implementations, without fixing
   * the order.
   */
  public function testSparseHookOrder(): void {
    $this->assertSameCallList(
      [
        // OOP and procedural listeners are correctly intermixed by module
        // order.
        'aaa_hook_order_test_sparse_test_hook',
        BHooks::class . '::sparseTestHook',
        'ccc_hook_order_test_sparse_test_hook',
        DHooks::class . '::sparseTestHook',
      ],
      \Drupal::moduleHandler()->invokeAll('sparse_test_hook'),
    );
  }

  /**
   * Tests hook order when both parameters are passed to RelativeOrderBase.
   *
   * This tests when both $modules and $classesAndMethods are passed as
   * parameters to OrderAfter.
   */
  public function testBothParametersHookOrder(): void {
    $this->assertSameCallList(
      [
        BHooks::class . '::testBothParametersHook',
        CHooks::class . '::testBothParametersHook',
        AHooks::class . '::testBothParametersHook',
      ],
      \Drupal::moduleHandler()->invokeAll('test_both_parameters_hook'),
    );
  }

  /**
   * Test procedural implementation with Reorder and Remove.
   */
  public function testHookReorderProcedural(): void {
    $this->assertSameCallList(
      [
        'bbb_hook_order_test_test_procedural_reorder',
        AHooks::class . '::testProceduralReorder',
      ],
      \Drupal::moduleHandler()->invokeAll('test_procedural_reorder'),
    );
  }

}
