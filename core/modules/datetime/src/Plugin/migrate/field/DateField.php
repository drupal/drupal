<?php

namespace Drupal\datetime\Plugin\migrate\field;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

// cspell:ignore todate

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

    // If the 'todate' setting is specified the field is now a 'daterange' and
    // so set the end value. If the datetime_range module is not enabled on the
    // destination then end_value is ignored and a message is logged in the
    // relevant migrate message table.
    if (!empty($field_data['settings']['todate'])) {
      $process['end_value'] = $process['value'];
      $process['end_value']['source'] = 'value2';
    }

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
  public function getFieldType(Row $row) {
    $field_type = parent::getFieldType($row);

    // If the 'todate' setting is specified then change the field type to
    // 'daterange' so we can migrate the end date.
    if ($field_type === 'datetime' && !empty($row->get('settings/todate'))) {
      if (\Drupal::service('module_handler')->moduleExists('datetime_range')) {
        return 'daterange';
      }
      else {
        throw new MigrateException(sprintf("Can't migrate field '%s' with 'todate' settings. Enable the datetime_range module. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#datetime", $row->get('field_name')));
      }
    }

    return $field_type;
  }

}
