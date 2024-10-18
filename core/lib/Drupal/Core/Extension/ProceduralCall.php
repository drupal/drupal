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
   * Calls a function in the root namespace.
   *
   * __call() does not support references https://bugs.php.net/bug.php?id=71256
   * Because of this, ModuleHandler::getHookListeners() inlines this
   * method so it is not called anywhere in core.
   */
  public function __call($name, $args): mixed {
    $this->loadFile($name);
    return ('\\' . $name)(... $args);
  }

  /**
   * Loads the file a function lives in, if any.
   *
   * @param $function
   *   The name of the function.
   */
  public function loadFile($function): void {
    if (isset($this->includes[$function])) {
      include_once $this->includes[$function];
    }
  }

}
