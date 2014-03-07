<?php

/**
 * @file
 * Contains \Drupal\Core\Installer\Exception\InstallerException.
 */

namespace Drupal\Core\Installer\Exception;

/**
 * Base class for exceptions thrown by installer.
 */
class InstallerException extends \RuntimeException {

  /**
   * The page title to output.
   *
   * @var string
   */
  protected $title;

  /**
   * The string translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs a new installer exception.
   *
   * @param string $title
   *   The page title.
   * @param string $message
   *   (optional) The exception message. Defaults to 'Error'.
   * @param int $code
   *   (optional) The exception code. Defaults to 0.
   * @param \Exception $previous
   *   (optional) A previous exception.
   */
  public function __construct($message, $title = 'Error', $code = 0, \Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->title = $title;
  }

  /**
   * Returns the exception page title.
   *
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Translates a string using StringTranslation.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface::translate()
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->stringTranslation->translate($string, $args, $options);
  }

}
