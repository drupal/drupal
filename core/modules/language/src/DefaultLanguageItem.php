<?php

namespace Drupal\language;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\LanguageItem;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ContentLanguageSettings;

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
      $langcode = $this->getDefaultLanguageCode($entity->getEntityTypeId(), $entity->bundle());
    }
    // Always notify otherwise default langcode will not be set correctly.
    $this->setValue(['value' => $langcode], TRUE);
    return $this;
  }

  /**
   * Provides default language code of a given an entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID of the entity whose code of language is to be loaded.
   * @param string $bundle
   *   The entity bundle of the entity whose code of language is to be loaded.
   *
   * @return string
   *   A string language code.
   */
  protected function getDefaultLanguageCode(string $entity_type_id, string $bundle): string {
    $language_manager = \Drupal::languageManager();
    $configuration = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);

    $default_value = NULL;
    $language_interface = $language_manager->getCurrentLanguage();
    switch ($configuration->getDefaultLangcode()) {
      case LanguageInterface::LANGCODE_SITE_DEFAULT:
        $default_value = $language_manager->getDefaultLanguage()->getId();
        break;

      case 'current_interface':
        $default_value = $language_interface->getId();
        break;

      case 'authors_default':
        $user = \Drupal::currentUser();
        $language_code = $user->getPreferredLangcode();
        if (!empty($language_code)) {
          $default_value = $language_code;
        }
        else {
          $default_value = $language_interface->getId();
        }
        break;
    }
    if ($default_value) {
      return $default_value;
    }

    // If we still do not have a default value, just return the value stored in
    // the configuration; it has to be an actual language code.
    return $configuration->getDefaultLangcode();
  }

  /**
   * Provides default language code of given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose language code to be loaded.
   *
   * @return string
   *   A string language code.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no
   *   public replacement.
   *
   * @see https://www.drupal.org/node/3566774
   */
  public function getDefaultLangcode(EntityInterface $entity) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no public replacement. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
    return $this->getDefaultLanguageCode($entity->getEntityTypeId(), $entity->bundle());
  }

}
