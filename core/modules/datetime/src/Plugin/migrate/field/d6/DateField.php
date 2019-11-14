<?php

namespace Drupal\datetime\Plugin\migrate\field\d6;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "date",
 *   type_map = {
 *     "date" = "datetime",
 *     "datestamp" =  "timestamp",
 *     "datetime" =  "datetime",
 *   },
 *   core = {6},
 *   source_module = "date",
 *   destination_module = "datetime",
 *   weight = 9999999,
 * )
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. Use
 * \Drupal\datetime\Plugin\migrate\field\DateField instead.
 */
class DateField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error('DateField is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\datetime\Plugin\migrate\field\DateField instead.', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'date' => 'datetime_default',
      'datetime' => 'datetime_default',
      'datestamp' => 'datetime_timestamp',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    switch ($data['type']) {
      case 'date':
        $from_format = 'Y-m-d\TH:i:s';
        $to_format = 'Y-m-d\TH:i:s';
        break;
      case 'datestamp':
        $from_format = 'U';
        $to_format = 'U';
        break;
      case 'datetime':
        $from_format = 'Y-m-d H:i:s';
        $to_format = 'Y-m-d\TH:i:s';
        break;
      default:
        throw new MigrateException(sprintf('Field %s of type %s is an unknown date field type.', $field_name, var_export($data['type'], TRUE)));
    }
    $process = [
      'value' => [
        'plugin' => 'format_date',
        'from_format' => $from_format,
        'to_format' => $to_format,
        'source' => 'value',
      ],
    ];

    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => $process,
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
