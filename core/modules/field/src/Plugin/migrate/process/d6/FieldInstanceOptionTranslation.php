<?php

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Determines the settings property and translation for boolean fields.
 */
#[MigrateProcess(
  id: "d6_field_instance_option_translation",
  handle_multiples: TRUE,
)]
class FieldInstanceOptionTranslation extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$field_type, $global_settings] = $value;

    $option_key = 0;
    $translation = '';
    if (isset($global_settings['allowed_values'])) {
      $list = explode("\n", $global_settings['allowed_values']);
      $list = array_map('trim', $list);
      $list = array_filter($list, 'strlen');
      switch ($field_type) {
        case 'boolean';
          $option = preg_replace('/^option_/', '', $row->getSourceProperty('property'));
          for ($i = 0; $i < 2; $i++) {
            $value = $list[$i];
            $tmp = explode("|", $value);
            $original_option_key = $tmp[0] ?? NULL;
            $option_key = ($i === 0) ? 'off_label' : 'on_label';
            // Find property with name matching the original option.
            if ($option == $original_option_key) {
              $translation = $row->getSourceProperty('translation');
              break;
            }
          }
          break;

        default:
      }
    }
    return ['settings.' . $option_key, $translation];
  }

}
