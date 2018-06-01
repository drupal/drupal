<?php

namespace Drupal\media\OEmbed;

/**
 * Exception thrown if an oEmbed resource causes an error.
 *
 * This differs from \Drupal\media\OEmbed\ResourceException in that it is only
 * thrown before a \Drupal\media\OEmbed\Resource value object has been created
 * for the resource.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 */
class RawResourceException extends ResourceException {

  /**
   * The URL of the resource.
   *
   * @var string
   */
  protected $url;

  /**
   * The resource data.
   *
   * @var array
   */
  protected $resource = [];

  /**
   * RawResourceException constructor.
   *
   * @param string $message
   *   The exception message.
   * @param string $url
   *   The URL of the resource. Can be the actual endpoint URL or the canonical
   *   URL.
   * @param array $resource
   *   (optional) The raw resource data.
   * @param \Exception $previous
   *   (optional) The previous exception, if any.
   */
  public function __construct($message, $url, array $resource = [], \Exception $previous = NULL) {
    $this->url = $url;
    $this->resource = $resource;
    parent::__construct($message, 0, $previous);
  }

  /**
   * Gets the URL of the resource which caused the exception.
   *
   * @return string
   *   The URL of the resource.
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Gets the raw resource data, if available.
   *
   * @return array
   *   The resource data.
   */
  public function getResource() {
    return $this->resource;
  }

}
