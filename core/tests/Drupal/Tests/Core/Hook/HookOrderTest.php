<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Hook;

use Drupal\Core\Hook\HookOrder;

/**
 * @coversDefaultClass \Drupal\Core\Hook\HookOrder
 *
 * @group Hook
 */
class HookOrderTest extends HookOrderTestBase {

  /**
   * The original priorities.
   *
   * @var array
   */
  protected array $originalPriorities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // "c" first, "b" second, "a" last.
    $this->setUpContainer(TRUE);
    foreach (['a', 'b', 'c'] as $key) {
      $this->originalPriorities[$key] = $this->getPriority($key);
    }
    // The documentation does not clarify the order of arguments, let's do so
    // here to make it easier to develop/debug this test.
    $this->assertGreaterThan(1, 2);
    // According to https://symfony.com/doc/current/event_dispatcher.html
    // the higher the number, the earlier a listener is executed. Accordingly
    // assert that "a" is last, "c" is first, "b" is in the middle. The
    // asserts in methods can be compared to these establishing asserts.
    $this->assertGreaterThan($this->getPriority('a'), $this->getPriority('b'));
    $this->assertGreaterThan($this->getPriority('b'), $this->getPriority('c'));
    // This is unnecessary but asserts are free, and it's easier to compare if
    // this is explicit.
    $this->assertGreaterThan($this->getPriority('a'), $this->getPriority('c'));
  }

  /**
   * @covers ::first
   */
  public function testFirst(): void {
    // "c" was first, make "a" the first.
    HookOrder::first($this->container, 'test', 'a::a');
    $this->assertGreaterThan($this->getPriority('c'), $this->getPriority('a'));
    $this->assertGreaterThan($this->getPriority('b'), $this->getPriority('a'));
    // The other two shouldn't change.
    $this->assertNoChange('a');
  }

  /**
   * @covers ::last
   */
  public function testLast(): void {
    // "c" was first, make it the last.
    HookOrder::last($this->container, 'test', 'c::c');
    $this->assertGreaterThan($this->getPriority('c'), $this->getPriority('a'));
    $this->assertGreaterThan($this->getPriority('c'), $this->getPriority('b'));
    // The other two shouldn't change.
    $this->assertNoChange('c');
  }

  /**
   * @covers ::before
   */
  public function testBefore(): void {
    // "a" was last, move it before "b".
    HookOrder::before($this->container, 'test', 'a::a', 'b::b');
    $this->assertGreaterThan($this->getPriority('b'), $this->getPriority('a'));
    // The relation between these should not change. The actual priority
    // might.
    $this->assertGreaterThan($this->getPriority('b'), $this->getPriority('c'));
    $this->assertGreaterThan($this->getPriority('a'), $this->getPriority('c'));
  }

  /**
   * @covers ::after
   */
  public function testAfter(): void {
    // "c" was first, move it after "b".
    HookOrder::after($this->container, 'test', 'c::c', 'b::b');
    $this->assertGreaterThan($this->getPriority('c'), $this->getPriority('b'));
    // The relation between these should not change. The actual priority
    // might.
    $this->assertGreaterThan($this->getPriority('a'), $this->getPriority('b'));
    $this->assertGreaterThan($this->getPriority('a'), $this->getPriority('c'));
  }

  /**
   * @covers ::first
   */
  public function testFirstNoChange(): void {
    // "c" was first, making it first should be a no-op.
    HookOrder::first($this->container, 'test', 'c::c');
    $this->assertNoChange();
  }

  /**
   * @covers ::last
   */
  public function testLastNoChange(): void {
    // "a" was last, making it last should be a no-op.
    HookOrder::last($this->container, 'test', 'a::a');
    $this->assertNoChange();
  }

  /**
   * @covers ::before
   */
  public function testBeforeNoChange(): void {
    // "b" is already firing before "a", this should be a no-op.
    HookOrder::before($this->container, 'test', 'b::b');
    $this->assertNoChange();
  }

  /**
   * @covers ::after
   */
  public function testAfterNoChange(): void {
    // "b' is already firing after "c", this should be a no-op.
    HookOrder::after($this->container, 'test', 'b::b');
    $this->assertNoChange();
  }

  /**
   * Get the priority for a service.
   */
  protected function getPriority(string $name): int {
    $definition = $this->container->getDefinition($name);
    return $definition->getTags()['kernel.event_listener'][0]['priority'];
  }

  /**
   * Asserts no change has happened.
   *
   * @param string $changed
   *   This one did change. Assert the rest did not change.
   */
  protected function assertNoChange(string $changed = ''): void {
    foreach ($this->originalPriorities as $key => $priority) {
      if ($key !== $changed) {
        $this->assertSame($priority, $this->getPriority($key));
      }
    }
  }

}
