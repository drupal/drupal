<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\aaa_hook_order_test\Hook\AHooks;
use Drupal\bbb_hook_order_test\Hook\BHooks;
use Drupal\ccc_hook_order_test\Hook\CHooks;
use Drupal\ddd_hook_order_test\Hook\DHooks;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Hook
 * @group legacy
 */
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

}
