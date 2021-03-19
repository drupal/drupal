<?php

namespace Drupal\datetime\Plugin\migrate\field;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;
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
      'format_interval' => 'datetime_time_ago',
      // The date_plain formatter exists in Drupal 7 but not Drupal 6. It is
      // added here because this plugin is declared for Drupal 6 and Drupal 7.
      'date_plain' => 'datetime_plain',
      // Drupal 6.
      // @see ::getFieldFormatterType
      'default' => 'datetime_default',
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
      'date_select' => 'datetime_default',
      'date_text' => 'datetime_default',
      'date_popup' => 'datetime_default',
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

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterType(Row $row) {
    if ($d6_formatter_type = $row->getSourceProperty('display_settings/format')) {
      // The Drupal 6 date formatter ID might be the machine name of the date
      // format, e.g. 'short', 'medium', 'long', or any other custom format.
      if ($d6_formatter_type !== 'format_interval') {
        return 'default';
      }
    }

    return parent::getFieldFormatterType($row);
  }

}
