<?php

namespace Drupal\Core\Field\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "email",
 *   core = {6,7},
 *   type_map = {
 *     "email" = "email"
 *   }
 * )
 */
class Email extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'email_textfield' => 'email_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'email_formatter_default' => 'basic_string',
      'email_formatter_contact' => 'basic_string',
      'email_formatter_plain' => 'basic_string',
      'email_formatter_spamspan' => 'basic_string',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'value' => 'email',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
