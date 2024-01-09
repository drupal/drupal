<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Traits;

use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\ConfigurableLanguageInterface;
use Drupal\language\ContentLanguageSettingsInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Provides an API to programmatically manage languages in tests.
 */
trait LanguageTestTrait {

  /**
   * Creates a configurable language object from a langcode.
   *
   * @param string $langcode
   *   The language code to use to create the object.
   *
   * @return \Drupal\Core\Language\ConfigurableLanguageInterface
   *   The created language.
   *
   * @see \Drupal\Core\Language\LanguageManager::getStandardLanguageList()
   */
  public static function createLanguageFromLangcode(string $langcode): ConfigurableLanguageInterface {
    $configurable_language = ConfigurableLanguage::createFromLangcode($langcode);
    $configurable_language->save();
    return $configurable_language;
  }

  /**
   * Enables translations for the given entity type bundle.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param string $bundle
   *   The bundle name.
   * @param string|null $default_langcode
   *   The language code to use as the default language.
   *
   * @return \Drupal\language\ContentLanguageSettingsInterface
   *   The saved content language config entity.
   */
  public static function enableBundleTranslation(string $entity_type_id, string $bundle, ?string $default_langcode = LanguageInterface::LANGCODE_SITE_DEFAULT): ContentLanguageSettingsInterface {
    $content_language_settings = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle)
      ->setDefaultLangcode($default_langcode)
      ->setLanguageAlterable(TRUE);
    $content_language_settings->save();
    return $content_language_settings;
  }

  /**
   * Disables translations for the given entity type bundle.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param string $bundle
   *   The bundle name.
   */
  public static function disableBundleTranslation(string $entity_type_id, string $bundle) {
    // @todo Move to API call when it exists, to be added at
    // https://www.drupal.org/project/drupal/issues/3408046
    $content_language_settings = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
    $content_language_settings->setLanguageAlterable(FALSE)
      ->save();
    $content_language_settings->delete();
  }

  /**
   * Sets and saves a given field instance translation status.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The name of the field.
   * @param bool $status
   *   Whether the field should be translatable or not.
   */
  public static function setFieldTranslatable(string $entity_type_id, string $bundle, string $field_name, bool $status): void {
    FieldConfig::loadByName($entity_type_id, $bundle, $field_name)
      ->setTranslatable($status)
      ->save();
  }

}
