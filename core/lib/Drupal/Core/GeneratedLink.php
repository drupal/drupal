<?php

/**
 * @file
 * Contains \Drupal\Core\GeneratedLink.
 */

namespace Drupal\Core;

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Used to return generated links, along with associated cacheability metadata.
 *
 * Note: not to be confused with \Drupal\Core\Link, which is for passing around
 *   ungenerated links (typically link text + route name + route parameters).
 */
class GeneratedLink extends BubbleableMetadata {

  /**
   * The HTML string value containing a link.
   *
   * @var string
   */
  protected $generatedLink = '';

  /**
   * Gets the generated link.
   *
   * @return string
   */
  public function getGeneratedLink() {
    return $this->generatedLink ;
  }

  /**
   * Sets the generated link.
   *
   * @param string $generated_link
   *   The generated link.
   *
   * @return $this
   */
  public function setGeneratedLink($generated_link) {
    $this->generatedLink = $generated_link;
    return $this;
  }

}
