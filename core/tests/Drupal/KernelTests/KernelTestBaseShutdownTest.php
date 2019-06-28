<?php

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
  protected function setUp() {
    // Initialize static variable prior to testing.
    self::$shutdownCalled = [];
    parent::setUp();
  }

  /**
   * @covers ::assertPostConditions
   */
  public function testShutdownFunction() {
    $this->expectedShutdownCalled = ['shutdownFunction', 'shutdownFunction2'];
    drupal_register_shutdown_function([$this, 'shutdownFunction']);
  }

  /**
   * @covers ::assertPostConditions
   */
  public function testNoShutdownFunction() {
    $this->expectedShutdownCalled = [];
  }

  /**
   * Registers that this shutdown function has been called.
   */
  public function shutdownFunction() {
    self::$shutdownCalled[] = 'shutdownFunction';
    drupal_register_shutdown_function([$this, 'shutdownFunction2']);
  }

  /**
   * Registers that this shutdown function has been called.
   */
  public function shutdownFunction2() {
    self::$shutdownCalled[] = 'shutdownFunction2';
  }

  /**
   * {@inheritdoc}
   */
  protected function assertPostConditions() {
    parent::assertPostConditions();
    $this->assertSame($this->expectedShutdownCalled, self::$shutdownCalled);
  }

}
