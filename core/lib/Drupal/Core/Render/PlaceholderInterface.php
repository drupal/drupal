<?php

namespace Drupal\Core\Render;

/**
 * Allows an element to provide a placeholder representation of itself.
 */
interface PlaceholderInterface {

  /**
   * Returns a string to be used as a placeholder.
   *
   * This is typically used when an element has no output and must be displayed,
   * for example during configuration.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   A placeholder string for this element.
   */
  public function getPlaceholderString();

}
