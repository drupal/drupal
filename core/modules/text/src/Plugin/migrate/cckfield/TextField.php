<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\migrate\cckfield\TextField.
 */

namespace Drupal\text\Plugin\migrate\cckfield;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

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
    $process = array(
      array(
        'plugin' => 'iterator',
        'source' => $field_name,
        // See \Drupal\migrate_drupal\Plugin\migrate\source\d6\User::baseFields(),
        // signature_format for an example of the YAML that represents this
        // process array.
        'process' => [
          'value' => 'value',
          'format' => [
            [
              'plugin' => 'static_map',
              'bypass' => TRUE,
              'source' => 'format',
              'map' => [0 => NULL],
            ],
            [
              'plugin' => 'skip_on_empty',
              'method' => 'process',
            ],
            [
              'plugin' => 'migration',
              'migration' => 'd6_filter_format',
              'source' => 'format',
            ],
          ],
        ],
      ),
    );
    $migration->setProcessOfProperty($field_name, $process);
  }

}
