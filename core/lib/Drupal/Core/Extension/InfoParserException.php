<?php
/**
 * @file
 * Contains Drupal\Core\Extension\InfoParserException.
 */

namespace Drupal\Core\Extension;

/**
 * An exception thrown by the InfoParser class whilst parsing info.yml files.
 */
class InfoParserException extends \RuntimeException {

  /**
   * The info.yml filename.
   *
   * @var string
   */
  protected $infoFilename;

  /**
   * Constructs the InfoParserException object.
   *
   * @param string $message
   *   The Exception message to throw.
   * @param string $filename
   *   The info.yml filename.
   */
  public function __construct($message, $info_filename) {
    $this->infoFilename = $info_filename;
    parent::__construct($message);
  }

  /**
   * Gets the info.yml filename.
   *
   * @return string
   *   The info.yml filename.
   */
  public function getInfoFilename () {
    return $this->infoFilename;
  }

}
