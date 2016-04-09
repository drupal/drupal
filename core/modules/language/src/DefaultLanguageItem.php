<?php

namespace Drupal\language;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\LanguageItem;
use Drupal\Core\Language\Language;

/**
 * Alternative plugin implementation of the 'language' field type.
 *
 * Replaces the Core 'language' entity field type implementation, changes the
 * default values used.
 *
 * Required settings are:
 *  - target_type: The entity type to reference.
 *
 * @see language_field_info_alter().
 */
class DefaultLanguageItem extends LanguageItem {

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to LANGCODE_NOT_SPECIFIED.
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    if ($entity = $this->getEntity()) {
      $langcode = $this->getDefaultLangcode($entity);
    }
    // Always notify otherwise default langcode will not be set correctly.
    $this->setValue(array('value' => $langcode), TRUE);
    return $this;
  }

  /**
   * Provides default language code of given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose language code to be loaded.
   *
   * @return string
   *  A string language code.
   */
  public function getDefaultLangcode(EntityInterface $entity) {
    return language_get_default_langcode($entity->getEntityTypeId(), $entity->bundle());
  }

}
