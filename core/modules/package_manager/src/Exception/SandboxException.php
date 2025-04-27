<?php

declare(strict_types=1);

namespace Drupal\package_manager\Exception;

use Drupal\package_manager\SandboxManagerBase;

/**
 * Base class for all exceptions related to stage operations.
 *
 * Should not be thrown by external code.
 */
class SandboxException extends \RuntimeException {

  /**
   * Constructs a StageException object.
   *
   * @param \Drupal\package_manager\SandboxManagerBase $sandboxManager
   *   The stage.
   * @param mixed ...$arguments
   *   Additional arguments to pass to the parent constructor.
   */
  public function __construct(public readonly SandboxManagerBase $sandboxManager, ...$arguments) {
    parent::__construct(...$arguments);
  }

}
