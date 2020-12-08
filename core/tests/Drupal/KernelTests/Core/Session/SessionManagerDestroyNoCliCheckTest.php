<?php

namespace Drupal\KernelTests\Core\Session;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests starting and destroying a session from the CLI.
 *
 * @group Session
 */
class SessionManagerDestroyNoCliCheckTest extends KernelTestBase {

  /**
   * Tests starting and destroying a session from the CLI.
   */
  public function testCallSessionManagerStartAndDestroy() {
    $this->assertFalse(\Drupal::service('session_manager')->start());
    $this->assertNull(\Drupal::service('session_manager')->destroy());
  }

}
