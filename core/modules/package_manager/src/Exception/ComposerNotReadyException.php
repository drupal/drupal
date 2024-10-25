<?php

declare(strict_types=1);

namespace Drupal\package_manager\Exception;

/**
 * Exception thrown if we cannot reliably use Composer.
 *
 * Should not be thrown by external code.
 *
 * @see \Drupal\package_manager\ComposerInspector::validate()
 */
final class ComposerNotReadyException extends \RuntimeException {

  /**
   * Constructs a ComposerNotReadyException object.
   *
   * @param string|null $workingDir
   *   The directory where Composer was run, or NULL if the errors are related
   *   to the Composer executable itself.
   * @param array $messages
   *   An array of messages explaining why Composer cannot be run correctly.
   * @param int $code
   *   (optional) The exception code. Defaults to 0.
   * @param \Throwable|null $previous
   *   (optional) The previous exception, for exception chaining.
   */
  public function __construct(public readonly ?string $workingDir, array $messages, int $code = 0, ?\Throwable $previous = NULL) {
    parent::__construct(implode("\n", $messages), $code, $previous);
  }

}
