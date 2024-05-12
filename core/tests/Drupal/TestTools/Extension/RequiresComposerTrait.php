<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension;

use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Ensures Composer executable is available, skips test otherwise.
 */
trait RequiresComposerTrait {

  #[BeforeClass]
  public static function requiresComposer(): void {
    if (!((new ExecutableFinder())->find('composer'))) {
      static::markTestSkipped('This test requires the Composer executable to be accessible.');
    }
  }

}
