<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

/**
 * @coversDefaultClass \Drupal\KernelTests\KernelTestBase
 *
 * @group PHPUnit
 * @group Test
 * @group KernelTests
 */
class KernelTestBaseShutdownTest extends KernelTestBase {

  /**
   * Indicates which shutdown functions are expected to be called.
   *
   * @var array
   */
  protected $expectedShutdownCalled;

  /**
   * Indicates which shutdown functions have been called.
   *
   * @var array
   */
  protected static $shutdownCalled;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Initialize static variable prior to testing.
    self::$shutdownCalled = [];
    parent::setUp();
  }

  /**
   * @covers ::assertPostConditions
   */
  public function testShutdownFunction(): void {
    $this->expectedShutdownCalled = ['shutdownFunction', 'shutdownFunction2'];
    drupal_register_shutdown_function([$this, 'shutdownFunction']);
  }

  /**
   * @covers ::assertPostConditions
   */
  public function testNoShutdownFunction(): void {
    $this->expectedShutdownCalled = [];
  }

  /**
   * Registers that this shutdown function has been called.
   */
  public function shutdownFunction(): void {
    self::$shutdownCalled[] = 'shutdownFunction';
    drupal_register_shutdown_function([$this, 'shutdownFunction2']);
  }

  /**
   * Registers that this shutdown function has been called.
   */
  public function shutdownFunction2(): void {
    self::$shutdownCalled[] = 'shutdownFunction2';
  }

  /**
   * {@inheritdoc}
   */
  protected function assertPostConditions(): void {
    parent::assertPostConditions();
    $this->assertSame($this->expectedShutdownCalled, self::$shutdownCalled);
  }

}
