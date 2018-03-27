<?php

namespace Drupal\datetime\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "datetime",
 *   type_map = {
 *     "date" = "datetime",
 *     "datestamp" =  "timestamp",
 *     "datetime" =  "datetime",
 *   },
<<<<<<< HEAD
 *   core = {6,7},
 *   source_module = "date",
 *   destination_module = "datetime"
=======
 *   core = {6,7}
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
 * )
 */
class DateField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
<<<<<<< HEAD
  public function getFieldFormatterMap() {
    return [
      'date_default' => 'datetime_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
=======
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
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
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
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
