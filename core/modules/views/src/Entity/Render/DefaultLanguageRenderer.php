<?php

namespace Drupal\views\Entity\Render;

use Drupal\views\ResultRow;

/**
 * Renders entities in their default language.
 */
class DefaultLanguageRenderer extends EntityTranslationRendererBase {

  /**
   * {@inheritdoc}
   */
  public function getLangcode(ResultRow $row, $relationship = 'none') {
    if ($entity = $this->getEntity($row, $relationship)) {
      return $entity->getUntranslated()->language()->getId();
    }
  }

}
