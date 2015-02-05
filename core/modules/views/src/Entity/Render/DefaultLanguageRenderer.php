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
   * {@inheritdoc}
   */
  public function getLangcode(ResultRow $row) {
    return $row->_entity->getUntranslated()->language()->getId();
  }

}
