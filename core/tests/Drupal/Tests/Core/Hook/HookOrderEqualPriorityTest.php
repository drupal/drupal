<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Hook;

use Drupal\Core\Hook\HookOrder;

/**
 * @coversDefaultClass \Drupal\Core\Hook\HookOrder
 *
 * @group Hook
 */
class HookOrderEqualPriorityTest extends HookOrderTestBase {

  protected function setUp(): void {
    parent::setUp();
    // The priority of "a", "b", "c" are the same, the order is undefined.
    $this->setUpContainer(FALSE);
    $this->assertSame($this->getPriority('a'), $this->getPriority('b'));
    $this->assertSame($this->getPriority('b'), $this->getPriority('c'));
  }

  /**
   * @covers ::first
   */
  public function testFirst(): void {
    // "c" was first, make "a" the first.
    HookOrder::first($this->container, 'test', 'a::a');
    $this->assertGreaterThan($this->getPriority('c'), $this->getPriority('a'));
    $this->assertGreaterThan($this->getPriority('b'), $this->getPriority('a'));
    // Nothing else can be asserted: by setting the same priority, the setup
    // had undefined order and so the services not included in the helper call
    // can be in any order.
  }

  /**
   * @covers ::last
   */
  public function testLast(): void {
    // "c" was first, make it the last.
    HookOrder::last($this->container, 'test', 'c::c');
    $this->assertGreaterThan($this->getPriority('c'), $this->getPriority('a'));
    $this->assertGreaterThan($this->getPriority('c'), $this->getPriority('b'));
    // Nothing else can be asserted: by setting the same priority, the setup
    // had undefined order and so the services not included in the helper call
    // can be in any order.
  }

  /**
   * @covers ::before
   */
  public function testBefore(): void {
    // "a" was last, move it before "b".
    HookOrder::before($this->container, 'test', 'a::a', 'b::b');
    $this->assertGreaterThan($this->getPriority('b'), $this->getPriority('a'));
    // Nothing else can be asserted: by setting the same priority, the setup
    // had undefined order and so the services not included in the helper call
    // can be in any order.
  }

  /**
   * @covers ::after
   */
  public function testAfter(): void {
    // "c" was first, move it after "b".
    HookOrder::after($this->container, 'test', 'c::c', 'b::b');
    $this->assertGreaterThan($this->getPriority('c'), $this->getPriority('b'));
    // Nothing else can be asserted: by setting the same priority, the setup
    // had undefined order and so the services not included in the helper call
    // can be in any order.
  }

}
