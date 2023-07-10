<?php

namespace Drupal\TestTools\Extension;

use Symfony\Component\Process\ExecutableFinder;

/**
 * Ensures Composer executable is available, skips test otherwise.
 */
trait RequiresComposerTrait {

  /**
   * @beforeClass
   */
  public static function requiresComposer(): void {
    if (!((new ExecutableFinder())->find('composer'))) {
      static::markTestSkipped('This test requires the Composer executable to be accessible.');
    }
  }

}
