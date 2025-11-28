<?php

namespace Drupal\language\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
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
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess('content_translation_enabled_setting')]
class ContentTranslationEnabledSetting extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('Input should be an array');
    }

    [$language_content_type, $entity_translation_entity_types, $entity_type] = $value;

    switch ($language_content_type) {
      // In the case of being 0, it will be skipped. We are not actually setting
      // a null value.
      case 0:
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
