<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Bootstrap;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests.
 *
 * @group Bootstrap
 */
class ShutdownFunctionTest extends KernelTestBase {

  /**
   * Flag to indicate if ::shutdownOne() called.
   *
   * @var bool
   */
  protected $shutDownOneCalled = FALSE;

  /**
   * Flag to indicate if ::shutdownTwo() called.
   *
   * @var bool
   */
  protected $shutDownTwoCalled = FALSE;

  /**
   * Tests that shutdown functions can be added by other shutdown functions.
   */
  public function testShutdownFunctionInShutdownFunction(): void {
    // Ensure there are no shutdown functions registered before starting the
    // test.
    $this->assertEmpty(drupal_register_shutdown_function());
    // Register a shutdown function that, when called, will register another
    // shutdown function.
    drupal_register_shutdown_function([$this, 'shutdownOne']);
    $this->assertCount(1, drupal_register_shutdown_function());

    // Simulate the Drupal shutdown.
    _drupal_shutdown_function();

    // Test that the expected functions are called.
    $this->assertTrue($this->shutDownOneCalled);
    $this->assertTrue($this->shutDownTwoCalled);
    $this->assertCount(2, drupal_register_shutdown_function());
  }

  /**
   * Tests shutdown functions by registering another shutdown function.
   */
  public function shutdownOne() {
    drupal_register_shutdown_function([$this, 'shutdownTwo']);
    $this->shutDownOneCalled = TRUE;
  }

  /**
   * Tests shutdown functions by being registered during shutdown.
   */
  public function shutdownTwo() {
    $this->shutDownTwoCalled = TRUE;
  }

}
