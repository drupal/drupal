<?php

namespace Drupal\user\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Determines the settings property and translation.
 *
 * @MigrateProcessPlugin(
 *   id = "d6_profile_field_option_translation",
 *   handle_multiples = TRUE
 * )
 */
class ProfileFieldOptionTranslation extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$field_type, $translation] = $value;

    $new_value = NULL;
    if (isset($translation)) {
      $allowed_values = [];
      $list = explode("\n", $translation);
      $list = array_map('trim', $list);
      $list = array_filter($list, 'strlen');
      if ($field_type === 'list_string') {
        foreach ($list as $value) {
          $allowed_values[] = ['label' => $value];
        }
      }
      $new_value = ['settings.allowed_values', $allowed_values];
    }
    return $new_value;
  }

}
