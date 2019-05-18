<?php

namespace Drupal\language\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Determines the content translation setting.
 *
 * The source value is an indexed array of three values:
 * - The language content type, e.g. '1'
 * - The entity_translation_entity_types, an array of entity types.
 * - An entity type used with entity translation, e.g. comment.
 *
 * @MigrateProcessPlugin(
 *   id = "content_translation_enabled_setting"
 * )
 */
class ContentTranslationEnabledSetting extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('Input should be an array');
    }

    list($language_content_type, $entity_translation_entity_types, $entity_type) = $value;

    switch ($language_content_type) {
      // In the case of being 0, it will be skipped. We are not actually setting
      // a null value.
      case 0;
        $setting = NULL;
        break;

      case 1:
        $setting = FALSE;
        break;

      case 2:
        $setting = FALSE;
        break;

      case 4:
        // If entity translation is enabled return the status of comment
        // translations.
        $setting = FALSE;
        if (!empty($entity_translation_entity_types[$entity_type])) {
          $setting = TRUE;
        }
        break;

      default:
        $setting = NULL;
        break;
    }
    return $setting;
  }

}
