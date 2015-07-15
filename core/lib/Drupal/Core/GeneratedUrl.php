<?php

/**
 * @file
 * Contains \Drupal\Core\GeneratedUrl.
 */

namespace Drupal\Core;

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Used to return generated URLs, along with associated bubbleable metadata.
 *
 * Note: not to be confused with \Drupal\Core\Url, which is for passing around
 *   ungenerated URLs (typically route name + route parameters).
 */
class GeneratedUrl extends BubbleableMetadata {

  /**
   * The string value of the URL.
   *
   * @var string
   */
  protected $generatedUrl = '';

  /**
   * Gets the generated URL.
   *
   * @return string
   */
  public function getGeneratedUrl() {
    return $this->generatedUrl ;
  }

  /**
   * Sets the generated URL.
   *
   * @param string $generated_url
   *   The generated URL.
   *
   * @return $this
   */
  public function setGeneratedUrl($generated_url) {
    $this->generatedUrl = $generated_url;
    return $this;
  }

}
