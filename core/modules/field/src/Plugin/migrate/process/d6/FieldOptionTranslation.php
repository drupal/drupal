<?php

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\Component\Utility\FilterArray;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Determines the allowed values translation for select lists.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess(
  id: "d6_field_option_translation",
  handle_multiples: TRUE,
)]
class FieldOptionTranslation extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * Get the field default/mapped settings.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$field_type, $global_settings] = $value;

    $allowed_values = '';
    $i = 0;
    if (isset($global_settings['allowed_values'])) {
      $list = explode("\n", $global_settings['allowed_values']);
      $list = array_map('trim', $list);
      $list = FilterArray::removeEmptyStrings($list);
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
