<?php

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

// cspell:ignore userreference

/**
 * Get the field settings.
 */
#[MigrateProcess('field_settings')]
class FieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Get the field default/mapped settings.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // To maintain backwards compatibility, ensure that $value contains at least
    // three elements.
    if (count($value) == 2) {
      $value[] = NULL;
    }
    [$field_type, $global_settings, $original_field_type] = $value;
    return $this->getSettings($field_type, $global_settings, $original_field_type);
  }

  /**
   * Merge the default D8 and specified D6 settings.
   *
   * @param string $field_type
   *   The destination field type.
   * @param array $global_settings
   *   The field settings.
   * @param string $original_field_type
   *   (optional) The original field type before migration.
   *
   * @return array
   *   A valid array of settings.
   */
  public function getSettings($field_type, $global_settings, $original_field_type = NULL) {
    $max_length = $global_settings['max_length'] ?? '';
    $max_length = empty($max_length) ? 255 : $max_length;
    $allowed_values = [];
    if (isset($global_settings['allowed_values'])) {
      $list = explode("\n", $global_settings['allowed_values']);
      $list = array_map('trim', $list);
      $list = array_filter($list, 'strlen');
      switch ($field_type) {
        case 'list_string':
        case 'list_integer':
        case 'list_float':
          foreach ($list as $value) {
            $value = explode("|", $value);
            $allowed_values[$value[0]] = $value[1] ?? $value[0];
          }
          break;

        default:
          $allowed_values = $list;
      }
    }

    $settings = [
      'text' => [
        'max_length' => $max_length,
      ],
      'datetime' => ['datetime_type' => 'datetime'],
      'list_string' => [
        'allowed_values' => $allowed_values,
      ],
      'list_integer' => [
        'allowed_values' => $allowed_values,
      ],
      'list_float' => [
        'allowed_values' => $allowed_values,
      ],
      'boolean' => [
        'allowed_values' => $allowed_values,
      ],
    ];

    if ($original_field_type == 'userreference') {
      return ['target_type' => 'user'];
    }
    else {
      return $settings[$field_type] ?? [];
    }
  }

}
