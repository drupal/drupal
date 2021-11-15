<?php

namespace Drupal\field\Plugin\migrate\process\d7;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Determines the settings property and translation for boolean fields.
 *
 * @MigrateProcessPlugin(
 *   id = "d7_field_instance_option_translation",
 *   handle_multiples = TRUE
 * )
 */
class FieldInstanceOptionTranslation extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$type, $data] = $value;

    $data = unserialize($data);
    $property = $row->getSourceProperty('property');
    $option_key = ($property == 0) ? 'off_label' : 'on_label';
    $translation = '';
    if (isset($data['settings']['allowed_values'])) {
      $allowed_values = $data['settings']['allowed_values'];
      switch ($type) {
        case 'boolean';
          if (isset($allowed_values[$property])) {
            $translation = $row->getSourceProperty('translation');
            break;
          }
          break;

        default:
      }
    }
    return ['settings.' . $option_key, $translation];
  }

}
