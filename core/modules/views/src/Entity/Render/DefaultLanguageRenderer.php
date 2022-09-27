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
  public function getLangcode(ResultRow $row) {
    return $row->_entity->getUntranslated()->language()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcodeByRelationship(ResultRow $row, string $relationship = 'none'): string {
    $entity = $this->getEntity($row, $relationship);
    return $entity->getUntranslated()->language()->getId();
  }

}
