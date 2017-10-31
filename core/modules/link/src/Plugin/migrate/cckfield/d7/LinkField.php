<?php

namespace Drupal\link\Plugin\migrate\cckfield\d7;

@trigger_error('LinkField is deprecated in Drupal 8.3.x and will be be removed before Drupal 9.0.x. Use \Drupal\link\Plugin\migrate\field\d7\LinkField instead.', E_USER_DEPRECATED);

use Drupal\link\Plugin\migrate\cckfield\LinkField as D6LinkField;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateCckField(
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
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\link\Plugin\migrate\field\d7\LinkField instead.
 *
 * @see https://www.drupal.org/node/2751897
 */
class LinkField extends D6LinkField {

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
