<?php

namespace Drupal\user\Plugin\migrate\process\d6;

use Drupal\Component\Utility\FilterArray;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Determines the settings property and translation.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess(
  id: "d6_profile_field_option_translation",
  handle_multiples: TRUE,
)]
class ProfileFieldOptionTranslation extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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
      $list = FilterArray::removeEmptyStrings($list);
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
