<?php

namespace Drupal\media\OEmbed;

/**
 * Exception thrown if an oEmbed resource cannot be fetched or parsed.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 */
class ResourceException extends \Exception {

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
  protected $data = [];

  /**
   * ResourceException constructor.
   *
   * @param string $message
   *   The exception message.
   * @param string $url
   *   The URL of the resource. Can be the actual endpoint URL or the canonical
   *   URL.
   * @param array $data
   *   (optional) The raw resource data, if available.
   * @param \Exception $previous
   *   (optional) The previous exception, if any.
   */
  public function __construct($message, $url, array $data = [], ?\Exception $previous = NULL) {
    $this->url = $url;
    $this->data = $data;
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
  public function getData() {
    return $this->data;
  }

}
