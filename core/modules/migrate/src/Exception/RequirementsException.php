<?php

namespace Drupal\migrate\Exception;

use Exception;

/**
 * Defines an
 *
 * @see \Drupal\migrate\Plugin\RequirementsInterface
 */
class RequirementsException extends \RuntimeException {

  /**
   * The missing requirements.
   *
   * @var array
   */
  protected $requirements;

  /**
   * Constructs a new RequirementsException instance.
   *
   * @param string $message
   *   (optional) The Exception message to throw.
   * @param array $requirements
   *   (optional) The missing requirements.
   * @param int $code
   *   (optional) The Exception code.
   * @param \Exception $previous
   *   (optional) The previous exception used for the exception chaining.
   */
  public function __construct($message = "", array $requirements = [], $code = 0, Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);

    $this->requirements = $requirements;
  }

  /**
   * Get an array of requirements.
   *
   * @return array
   *   The requirements.
   */
  public function getRequirements() {
    return $this->requirements;
  }

  /**
   * Get the requirements as a string.
   *
   * @return string
   *   A formatted requirements string.
   */
  public function getRequirementsString() {
    $output = '';
    foreach ($this->requirements as $requirement_type => $requirements) {
      if (!is_array($requirements)) {
        $requirements = array($requirements);
      }

      foreach ($requirements as $value) {
        $output .= "$requirement_type: $value. ";
      }
    }
    return trim($output);
  }

}
