<?php

/**
 * @file
 * Contains \Drupal\views\Entity\Render\CurrentLanguageRenderer.
 */

namespace Drupal\views\Entity\Render;

use Drupal\views\ResultRow;

/**
 * Renders entities in the current language.
 */
class CurrentLanguageRenderer extends RendererBase {

  /**
   * Returns NULL so that the current language is used.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   */
  protected function getLangcode(ResultRow $row) {
  }

}
