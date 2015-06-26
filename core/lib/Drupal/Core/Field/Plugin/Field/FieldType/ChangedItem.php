<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

/**
 * Defines the 'changed' entity field type.
 *
 * Based on a field of this type, entity types can easily implement the
 * EntityChangedInterface.
 *
 * @FieldType(
 *   id = "changed",
 *   label = @Translation("Last changed"),
 *   description = @Translation("An entity field containing a UNIX timestamp of when the entity has been last updated."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\Core\Field\ChangedFieldItemList"
 * )
 *
 * @see \Drupal\Core\Entity\EntityChangedInterface
 */
class ChangedItem extends CreatedItem {

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    // Set the timestamp to request time if it is not set.
    if (!$this->value) {
      $this->value = REQUEST_TIME;
    }
    else {
      // On an existing entity the changed timestamp will only be set to request
      // time automatically if at least one other field value of the entity has
      // changed. This detection doesn't run on new entities and will be turned
      // off if the changed timestamp is set manually before save, for example
      // during migrations or using
      // \Drupal\content_translation\ContentTranslationMetadataWrapperInterface::setChangedTime().
      // @todo Knowing if the current translation was modified or not is
      //   generally useful. There's a follow-up issue to reduce the nesting
      //   here and to offer an accessor for this information. See
      //   https://www.drupal.org/node/2453153
      $entity = $this->getEntity();
      if (!$entity->isNew()) {
        $field_name = $this->getFieldDefinition()->getName();
        // Clone $entity->original to avoid modifying it when calling
        // getTranslation().
        $original = clone $entity->original;
        $translatable = $this->getFieldDefinition()->isTranslatable();
        if ($translatable) {
          $original = $original->getTranslation($entity->language()->getId());
        }
        if ($this->value == $original->get($field_name)->value) {
          foreach ($entity->getFieldDefinitions() as $other_field_name => $other_field_definition) {
            if ($other_field_name != $field_name && !$other_field_definition->isComputed() && (!$translatable || $other_field_definition->isTranslatable())) {
              $items = $entity->get($other_field_name)->filterEmptyItems();
              $original_items = $original->get($other_field_name)->filterEmptyItems();
              if (!$items->equals($original_items)) {
                $this->value = REQUEST_TIME;
                break;
              }
            }
          }
        }
      }
    }
  }

}
