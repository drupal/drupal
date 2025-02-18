<?php

declare(strict_types=1);

namespace Drupal\Core\Extension;

/**
 * Calls procedural hook implementations for backwards compatibility.
 *
 * @internal
 */
final class ProceduralCall {

  /**
   * @param array $includes
   *   An associated array, key is a function name, value is the name of the
   *   include file the function lives in, if any.
   */
  public function __construct(protected array $includes) {

  }

  /**
   * Loads the file a function lives in, if any.
   *
   * @param string $function
   *   The name of the function.
   */
  public function loadFile($function): void {
    if (isset($this->includes[$function])) {
      include_once $this->includes[$function];
    }
  }

}
