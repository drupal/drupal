<?php

namespace Drupal\media\OEmbed;

/**
 * Exception thrown if an oEmbed resource causes an error.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 */
class ResourceException extends \Exception {

  /**
   * The resource which caused the exception.
   *
   * @var \Drupal\media\OEmbed\Resource
   */
  protected $resource;

  /**
   * ResourceException constructor.
   *
   * @param string $message
   *   The exception message.
   * @param \Drupal\media\OEmbed\Resource $resource
   *   (optional) The value object for the resource.
   * @param \Exception $previous
   *   (optional) The previous exception, if any.
   */
  public function __construct($message, Resource $resource = NULL, \Exception $previous = NULL) {
    $this->resource = $resource;
    parent::__construct($message, 0, $previous);
  }

  /**
   * Gets the resource which caused the exception, if available.
   *
   * @return \Drupal\media\OEmbed\Resource|null
   *   The oEmbed resource.
   */
  public function getResource() {
    return $this->resource;
  }

}
