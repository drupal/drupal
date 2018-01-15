<?php

namespace Drupal\serialization\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Converts empty reference values for entity reference items.
 */
trait EntityReferenceFieldItemNormalizerTrait {

  protected function normalizeRootReferenceValue(&$values, EntityReferenceItem $field_item) {
    // @todo Generalize for all tree-structured entity types.
    if ($this->fieldItemReferencesTaxonomyTerm($field_item) && empty($values['target_id'])) {
      $values['target_id'] = NULL;
    }
  }

  /**
   * Determines if a field item references a taxonomy term.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $field_item
   *
   * @return bool
   */
  protected function fieldItemReferencesTaxonomyTerm(EntityReferenceItem $field_item) {
    return $field_item->getFieldDefinition()->getSetting('target_type') === 'taxonomy_term';
  }

}
