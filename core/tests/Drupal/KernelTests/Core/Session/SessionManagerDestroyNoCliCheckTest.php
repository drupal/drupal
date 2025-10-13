<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Session;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests starting and destroying a session from the CLI.
 */
#[Group('Session')]
#[RunTestsInSeparateProcesses]
class SessionManagerDestroyNoCliCheckTest extends KernelTestBase {

  /**
   * Tests starting and destroying a session from the CLI.
   */
  public function testCallSessionManagerStartAndDestroy(): void {
    $this->assertFalse(\Drupal::service('session_manager')->start());
    $this->assertNull(\Drupal::service('session_manager')->destroy());
  }

}
