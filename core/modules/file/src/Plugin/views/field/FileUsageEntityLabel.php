<?php

namespace Drupal\file\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLabel;
use Drupal\views\ResultRow;

/**
 * Field handler to provide label of entity that uses the file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_usage_entity_label")
 */
class FileUsageEntityLabel extends EntityLabel {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $label = parent::render($values);
    // If label is returned, then it's a Drupal entity.
    if ($label) {
      return $label;
    }

    // If parent returned NULL, then we're dealing with non-entity usage.
    // It can't have link, so we remove it.
    $this->options['alter']['make_link'] = FALSE;
    return $this->sanitizeValue($this->getValue($values));
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    $entity_ids_per_type = [];
    foreach ($values as $value) {
      if ($type = $this->getValue($value, 'type')) {
        $entity_ids_per_type[$type][] = $this->getValue($value);
      }
    }

    foreach ($entity_ids_per_type as $type => $ids) {
      // The 'type' is not required to be an entity type.
      if ($this->entityTypeManager->hasHandler($type, 'storage')) {
        $this->loadedReferencers[$type] = $this->entityTypeManager->getStorage($type)->loadMultiple($ids);
      }
    }
  }

}
