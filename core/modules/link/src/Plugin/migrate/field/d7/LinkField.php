<?php

namespace Drupal\link\Plugin\migrate\field\d7;

use Drupal\link\Plugin\migrate\field\d6\LinkField as D6LinkField;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateField(
 *   id = "link_field",
 *   core = {7},
 *   type_map = {
 *     "link_field" = "link"
 *   },
 *   source_module = "link",
 *   destination_module = "link"
 * )
 *
 * This plugin provides the exact same functionality as the Drupal 6 "link"
 * plugin with the exception that the plugin ID "link_field" is used in the
 * field type map.
 */
class LinkField extends D6LinkField {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'link_default' => 'link',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    // By default, use the plugin ID for the widget types.
    return ['link_field' => 'link_default'];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldInstance(MigrationInterface $migration) {
    $process = [
      'plugin' => 'static_map',
      'source' => 'settings/title',
      'bypass' => TRUE,
      'map' => [
        'disabled' => DRUPAL_DISABLED,
        'optional' => DRUPAL_OPTIONAL,
        'required' => DRUPAL_REQUIRED,
      ],
    ];
    $migration->mergeProcessOfProperty('settings/title', $process);
  }

}
