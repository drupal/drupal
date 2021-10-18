<?php

namespace Drupal\field\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

// cspell:ignore spamspan

/**
 * MigrateField Plugin for Drupal 6 and 7 email fields.
 *
 * @MigrateField(
 *   id = "email",
 *   core = {6,7},
 *   type_map = {
 *     "email" = "email"
 *   },
 *   source_module = "email",
 *   destination_module = "core"
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
      'email_formatter_default' => 'email_mailto',
      'email_formatter_contact' => 'basic_string',
      'email_formatter_plain' => 'basic_string',
      'email_formatter_spamspan' => 'basic_string',
      'email_default' => 'email_mailto',
      'email_contact' => 'basic_string',
      'email_plain' => 'basic_string',
      'email_spamspan' => 'basic_string',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
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
