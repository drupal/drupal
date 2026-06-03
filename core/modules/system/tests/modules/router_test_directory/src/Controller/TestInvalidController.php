<?php

declare(strict_types=1);

namespace Drupal\router_test\Controller;

/**
 * A structurally invalid controller that cannot be autoloaded.
 */
class TestInvalidController {

  /**
   * An abstract method, which is not allowed on a non-abstract class.
   *
   * @return array
   *   A render array.
   */
  abstract public function build(): array;

}
