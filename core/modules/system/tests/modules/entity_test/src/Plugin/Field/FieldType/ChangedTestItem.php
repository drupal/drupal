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
      // to set a real time value here. For the stability of the test, set the
      // time of the original language to the current time plus just over one
      // second to simulate two different request times.
      // @todo mock the time service in https://www.drupal.org/node/2908210.
      if ($this->getEntity()->language()->isDefault()) {
        // Wait 1.1 seconds because time_sleep_until() is not reliable.
        time_sleep_until(time() + 1.1);
      }
      $this->value = time();
    }
  }

}
