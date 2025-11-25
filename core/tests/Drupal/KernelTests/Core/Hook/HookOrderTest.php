<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\aaa_hook_order_test\Hook\AHooks;
use Drupal\aaa_hook_order_test\Hook\AMissingTargetHooks;
use Drupal\bbb_hook_order_test\Hook\BHooks;
use Drupal\bbb_hook_order_test\Hook\BMissingTargetHooks;
use Drupal\ccc_hook_order_test\Hook\CHooks;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\Core\Hook\OrderOperation\BeforeOrAfter;
use Drupal\Core\Hook\OrderOperation\FirstOrLast;
use Drupal\ddd_hook_order_test\Hook\DHooks;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Hook Order.
 *
 * Tests use several of the hooks defined in test modules aaa_hook-order_test,
 * bbb_hook-order_test, ccc_hook-order_test, and ddd_hook-order_test. The hooks
 * are implemented using different combinations of Object Oriented (OO) and
 * procedural methods. In a single module hooks may only implemented
 * procedurally, or only by OO, or by both.
 */
#[CoversClass(BeforeOrAfter::class)]
#[CoversClass(FirstOrLast::class)]
#[CoversClass(OrderAfter::class)]
#[CoversClass(RemoveHook::class)]
#[CoversClass(ReorderHook::class)]
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
   * Tests hook order using OO and procedural implementations of 'test_hook'.
   *
   * The order of 'test_hook', without modification would be as follows.
   *   - aaa_hook_order_test_test_hook
   *   - \Drupal\aaa_hook_order_test\Hook\AHooks::testHook()
   *   - \Drupal\aaa_hook_order_test\Hook\AHooks::testHookAfterB()
   *   - \Drupal\aaa_hook_order_test\Hook\AHooks::testHookFirst()
   *   - \Drupal\aaa_hook_order_test\Hook\AHooks::testHookLast()
   *   - bbb_hook_order_test_test_hook()
   *   - \Drupal\bbb_hook_order_test\Hook\BHooks::testHook()
   *   - ccc_hook_order_test_test_hook()
   *   - \Drupal\ccc_hook_order_test\Hook\CHooks::testHook()
   *   - \Drupal\ccc_hook_order_test\Hook\CHooks::testHookFirst()
   *   - \Drupal\ccc_hook_order_test\Hook\CHooks::testHookRemoved()
   *   - \Drupal\ccc_hook_order_test\Hook\CHooks::testHookReorderFirst()
   *   - ddd_hook_order_test_test_hook()
   *   - \Drupal\ddd_hook_order_test\Hook\DHooks::testHook()
   *
   * That order is modified in the hook implementation as follows, and the
   * resulting order asserted in this test.
   *  - The Order attribute is used to move:
   *    - \Drupal\aaa_hook_order_test\Hook\AHooks::testHookFirst() first
   *    - \Drupal\aaa_hook_order_test\Hook\AHooks::testHookLast() last
   *    - \Drupal\ccc_hook_order_test\Hook\CHooks::testHookFirst() first
   *  - The OrderAfter attribute is used to move
   *    - \Drupal\aaa_hook_order_test\Hook\AHooks::testHookAfterB() to after the
   *       module bbb_hook_order_test.
   *  - The Reorder attribute is used to move
   *    - \Drupal\ccc_hook_order_test\Hook\CHooks::testHookReorderFirst() to the
   *      first position.
   *  - The Remove attribute is used to remove
   *    - \Drupal\ccc_hook_order_test\Hook\CHooks::testHookRemoved().
   *
   * @see \Drupal\aaa_hook_order_test\Hook\AHooks()
   * @see \Drupal\bbb_hook_order_test\Hook\BHooks()
   * @see \Drupal\ccc_hook_order_test\Hook\CHooks()
   * @see \Drupal\ddd_hook_order_test\Hook\DHooks()
   * @see \aaa_hook_order_test_test_hook()
   * @see \bbb_hook_order_test_test_hook()
   * @see \ccc_hook_order_test_test_hook()
   * @see \ddd_hook_order_test_test_hook()
   */
  public function testHookOrder(): void {
    $this->assertSameCallList(
      [
        // Moved to first using the ReorderHook attribute.
        CHooks::class . '::testHookReorderFirst',
        // Moved to first using Order::First.
        CHooks::class . '::testHookFirst',
        // Moved to first using Order::First.
        AHooks::class . '::testHookFirst',
        'aaa_hook_order_test_test_hook',
        AHooks::class . '::testHook',
        'bbb_hook_order_test_test_hook',
        BHooks::class . '::testHook',
        // Moved to after BHooks using OrderAfter().
        AHooks::class . '::testHookAfterB',
        'ccc_hook_order_test_test_hook',
        CHooks::class . '::testHook',
        'ddd_hook_order_test_test_hook',
        DHooks::class . '::testHook',
        // Moved to first using Order::Last.
        AHooks::class . '::testHookLast',
      ],
      \Drupal::moduleHandler()->invokeAll('test_hook'),
    );
  }

  /**
   * Tests the ReorderHook attribute with a missing target.
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
   * Tests hook order when each module has either OO or procedural listeners.
   *
   * This test uses the hook, 'sparse_test_hook'.
   *
   * This detects a possible mistake where we would first collect modules from
   * all procedural and then from all OO implementations, without fixing the
   * order.
   *
   * @see \Drupal\bbb_hook_order_test\Hook\BHooks::sparseTestHook()
   * @see \Drupal\ddd_hook_order_test\Hook\DHooks::sparseTestHook()
   * @see \aaa_hook_order_test_sparse_test_hook()
   * @see \ccc_hook_order_test_sparse_test_hook()
   */
  public function testSparseHookOrder(): void {
    $this->assertSameCallList(
      [
        // OO and procedural listeners are correctly intermixed by module
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
   * Tests ordering of 'test_both_parameters_hook' with all parameters defined.
   *
   * This tests when both $modules and $classesAndMethods are passed as
   * parameters to OrderAfter.
   *
   * The order of 'test_both_parameters_hook', without modification would be as
   * follows.
   * - \Drupal\aaa_hook_order_test\Hook\AHooks::testBothParametersHook()
   * - \Drupal\bbb_hook_order_test\Hook\BHooks::testBothParametersHook()
   * - \Drupal\ccc_hook_order_test\Hook\CHooks::testBothParametersHook()
   *
   * That order is modified in the hook implementation as follows, and the
   * resulting order asserted in this test.
   * The Order class is used to move:
   *  - Drupal\aaa_hook_order_test\Hook\AHooks::testBothParametersHook() after
   *   module bbb_hook_order_test, and
   *   \Drupal\ccc_hook_order_test\Hook\CHooks::testBothParametersHook()
   *
   * @see \Drupal\aaa_hook_order_test\Hook\AHooks::testBothParametersHook()
   * @see \Drupal\bbb_hook_order_test\Hook\BHooks::testBothParametersHook()
   * @see \Drupal\ccc_hook_order_test\Hook\CHooks::testBothParametersHook()
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
   * Test procedural hook ordering using, 'test_procedural_reorder'.
   *
   * The order of 'test_procedural_reorder', without modification would be as
   * follows.
   * - \Drupal\aaa_hook_order_test\Hook\AHooks::testProceduralReorder()
   * - bbb_hook_order_test_test_procedural_reorder()
   * - ccc_hook_order_test_test_procedural_reorder()
   *
   * That order is modified using the Remove and Reorder as follows and the
   *  resulting order asserted in this test.
   * - The Reorder attribute is used to move
   *   - bbb_hook_order_test_test_procedural_reorder() to the first position.
   * - The Remove attribute is used to remove
   *   - ccc_hook_order_test_test_procedural_reorder()
   *
   * @see \bbb_hook_order_test_test_procedural_reorder()
   * @see \ccc_hook_order_test_test_procedural_reorder()
   * @see \Drupal\aaa_hook_order_test\Hook\AHooks::testProceduralReorder()
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
