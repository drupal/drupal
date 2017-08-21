<?php

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
 *   default_widget = "datetime_timestamp",
 *   default_formatter = "timestamp",
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
      // On an existing entity translation, the changed timestamp will only be
      // set to the request time automatically if at least one other field value
      // of the entity has changed. This detection does not run on new entities
      // and will be turned off if the changed timestamp is set manually before
      // save, for example during migrations or by using
      // \Drupal\content_translation\ContentTranslationMetadataWrapperInterface::setChangedTime().
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->getEntity();
      /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
      $original = $entity->original;
      $langcode = $entity->language()->getId();
      if (!$entity->isNew() && $original->hasTranslation($langcode)) {
        $original_value = $original->getTranslation($langcode)->get($this->getFieldDefinition()->getName())->value;
        if ($this->value == $original_value && $entity->hasTranslationChanges()) {
          $this->value = REQUEST_TIME;
        }
      }
    }
  }

}
