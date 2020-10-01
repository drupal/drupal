<?php

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Determines the allowed values translation for select lists.
 *
 * @MigrateProcessPlugin(
 *   id = "d6_field_option_translation",
 *   handle_multiples = TRUE
 * )
 */
class FieldOptionTranslation extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Get the field default/mapped settings.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($field_type, $global_settings) = $value;

    $allowed_values = '';
    $i = 0;
    if (isset($global_settings['allowed_values'])) {
      $list = explode("\n", $global_settings['allowed_values']);
      $list = array_map('trim', $list);
      $list = array_filter($list, 'strlen');
      switch ($field_type) {
        case 'list_string':
        case 'list_integer':
        case 'list_float':
          // Remove the prefix used in the i18n_strings table for field options
          // to get the option value.
          $option = preg_replace('/^option_/', '', $row->getSourceProperty('property'));
          $i = 0;
          foreach ($list as $allowed_value) {
            // Get the key for this allowed value which may be a key|label pair
            // or just key.
            $value = explode("|", $allowed_value);
            if (isset($value[0]) && ($value[0] == $option)) {
              $allowed_values = ['label' => $row->getSourceProperty('translation')];
              break;
            }
            $i++;
          }
          break;

        default:
      }
    }
    return ["settings.allowed_values.$i", $allowed_values];
  }

}
