<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\cckfield\TextField.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\cckfield;

use Drupal\migrate\Entity\MigrationInterface;

/**
 * @PluginID("text")
 */
class TextField extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'text_textfield' => 'text_textfield',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'text_default',
      'trimmed' => 'text_trimmed',
      'plain' => 'basic_string',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    // The data is stored differently depending on whether we're using
    // db storage.
    $value_key = $data['db_storage'] ? $field_name : "$field_name/value";
    $format_key = $data['db_storage'] ? $field_name . '_format' : "$field_name/format" ;

    $migration->setProcessOfProperty("$field_name/value", $value_key);

    // See \Drupal\migrate_drupal\Plugin\migrate\source\d6\User::baseFields(),
    // signature_format for an example of the YAML that represents this
    // process array.
    $process = [
      [
        'plugin' => 'static_map',
        'bypass' => TRUE,
        'source' => $format_key,
        'map' => [0 => NULL]
      ],
      [
        'plugin' => 'skip_on_empty',
        'method' => 'process',
      ],
      [
        'plugin' => 'migration',
        'migration' => 'd6_filter_format',
        'source' => $format_key,
      ],
    ];
    $migration->mergeProcessOfProperty("$field_name/format", $process);
  }

}
