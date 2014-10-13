<?php

/**
 * @file
 * Contains \Drupal\views\Entity\Render\DefaultLanguageRenderer.
 */

namespace Drupal\views\Entity\Render;

use Drupal\views\ResultRow;

/**
 * Renders entities in their default language.
 */
class DefaultLanguageRenderer extends RendererBase {

  /**
   * Returns the language code associated to the given row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return string
   *   A language code.
   */
  protected function getLangcode(ResultRow $row) {
    return $row->_entity->getUntranslated()->language()->getId();
  }

}
