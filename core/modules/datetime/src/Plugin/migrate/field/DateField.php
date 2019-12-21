<?php

namespace Drupal\datetime\Plugin\migrate\field;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Provides a field plugin for date and time fields.
 *
 * @MigrateField(
 *   id = "datetime",
 *   type_map = {
 *     "date" = "datetime",
 *     "datestamp" =  "timestamp",
 *     "datetime" =  "datetime",
 *   },
 *   core = {6,7},
 *   source_module = "date",
 *   destination_module = "datetime"
 * )
 */
class DateField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'date_default' => 'datetime_default',
    ];
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
    $to_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    if (isset($data['field_definition']['data'])) {
      $field_data = unserialize($data['field_definition']['data']);
      if (isset($field_data['settings']['granularity'])) {
        $granularity = $field_data['settings']['granularity'];
        $collected_date_attributes = is_numeric(array_keys($granularity)[0])
          ? $granularity
          : array_keys(array_filter($granularity));
        if (empty(array_intersect($collected_date_attributes, ['hour', 'minute', 'second']))) {
          $to_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
        }
      }
    }

    switch ($data['type']) {
      case 'date':
        $from_format = 'Y-m-d\TH:i:s';
        break;

      case 'datestamp':
        $from_format = 'U';
        $to_format = 'U';
        break;

      case 'datetime':
        $from_format = 'Y-m-d H:i:s';
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
