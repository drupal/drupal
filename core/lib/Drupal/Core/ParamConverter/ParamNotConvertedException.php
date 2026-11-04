<?php

namespace Drupal\Core\ParamConverter;

/**
 * Provides an exception class for a request parameter that was not converted.
 */
class ParamNotConvertedException extends \Exception {

  /**
   * Constructs the ParamNotConvertedException.
   *
   * @param string $message
   *   The Exception message to throw.
   * @param int $code
   *   The Exception code.
   * @param \Throwable $previous
   *   The previous exception used for the exception chaining.
   * @param string $routeName
   *   The route name that was not converted.
   * @param array $rawParameters
   *   The raw parameters that were not converted.
   */
  public function __construct(
    $message = "",
    $code = 0,
    ?\Throwable $previous = NULL,
    protected $routeName = "",
    protected array $rawParameters = [],
  ) {
    parent::__construct($message, $code, $previous);
  }

  /**
   * Get the route name that was not converted.
   *
   * @return string
   *   The route name that was not converted.
   */
  public function getRouteName() {
    return $this->routeName;
  }

  /**
   * Get the raw parameters that were not converted.
   *
   * @return array
   *   The raw parameters that were not converted.
   */
  public function getRawParameters() {
    return $this->rawParameters;
  }

}
