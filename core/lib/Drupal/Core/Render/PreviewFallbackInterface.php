<?php

namespace Drupal\Core\Render;

/**
 * Allows an element to provide a fallback representation of itself for preview.
 */
interface PreviewFallbackInterface {

  /**
   * Returns a string to be used as a fallback during preview.
   *
   * This is typically used when an element has no output and must be displayed,
   * for example during configuration.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   A string representing for this.
   */
  public function getPreviewFallbackString();

}
