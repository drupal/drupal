<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Block;

use Drupal\Core\Path\PathMatcher;

/**
 * A class extending PatchMatcher for testing purposes.
 */
class StubPathMatcher extends PathMatcher {

  /**
   * {@inheritdoc}
   */
  public function isFrontPage(): bool {
    return FALSE;
  }

}
