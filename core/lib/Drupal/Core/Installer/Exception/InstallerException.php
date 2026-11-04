<?php

namespace Drupal\Core\Installer\Exception;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for exceptions thrown by installer.
 */
class InstallerException extends \RuntimeException {
  use StringTranslationTrait;

  /**
   * Constructs a new installer exception.
   *
   * @param string $message
   *   The exception message.
   * @param string $title
   *   (optional) The page title. Defaults to 'Error'.
   * @param int $code
   *   (optional) The exception code. Defaults to 0.
   * @param \Throwable $previous
   *   (optional) A previous exception.
   */
  public function __construct(
    $message,
    protected $title = 'Error',
    $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, $code, $previous);
  }

  /**
   * Returns the exception page title.
   *
   * @return string
   *   The page title.
   */
  public function getTitle() {
    return $this->title;
  }

}
