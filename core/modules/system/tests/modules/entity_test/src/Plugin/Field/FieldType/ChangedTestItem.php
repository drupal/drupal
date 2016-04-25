<?php

namespace Drupal\entity_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem;

/**
 * Defines the 'changed_test' entity field type.
 *
 * Wraps Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem.
 *
 * @FieldType(
 *   id = "changed_test",
 *   label = @Translation("Last changed"),
 *   description = @Translation("An entity field containing a UNIX timestamp of when the entity has been last updated."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\Core\Field\ChangedFieldItemList"
 * )
 *
 * @see \Drupal\Core\Entity\EntityChangedInterface
 */
class ChangedTestItem extends ChangedItem {

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    if ($this->value == REQUEST_TIME) {
      // During a test the request time is immutable. To allow tests of the
      // algorithm of
      // Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem::preSave() we need
      // to set a real time value here.
      $this->value = time();
    }
  }

}
