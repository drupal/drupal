<?php

namespace Drupal\field\Plugin\migrate\process\d7;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d7_field_instance_settings"
 * )
 */
class FieldInstanceSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($instance_settings, $widget_settings, $field_definition) = $value;
    $widget_type = $widget_settings['type'];

    $field_data = unserialize($field_definition['data']);

    // Get taxonomy term reference handler settings from allowed values.
    if ($row->getSourceProperty('type') == 'taxonomy_term_reference') {
      $instance_settings['handler_settings']['sort'] = [
        'field' => '_none',
      ];
      $allowed_values = $row->get('@allowed_values');
      foreach ($allowed_values as $allowed_value) {
        foreach ($allowed_value as $vocabulary) {
          $instance_settings['handler_settings']['target_bundles'][$vocabulary] = $vocabulary;
        }
      }
    }

    // Get entityreference handler settings from source field configuration.
    if ($row->getSourceProperty('type') == "entityreference") {
      $field_settings = $field_data['settings'];
      $instance_settings['handler'] = 'default:' . $field_settings['target_type'];
      // Transform the sort settings to D8 structure.
      $sort = [
        'field' => '_none',
        'direction' => 'ASC',
      ];
      if (!empty(array_filter($field_settings['handler_settings']['sort']))) {
        if ($field_settings['handler_settings']['sort']['type'] == "property") {
          $sort = [
            'field' => $field_settings['handler_settings']['sort']['property'],
            'direction' => $field_settings['handler_settings']['sort']['direction'],
          ];
        }
        elseif ($field_settings['handler_settings']['sort']['type'] == "field") {
          $sort = [
            'field' => $field_settings['handler_settings']['sort']['field'],
            'direction' => $field_settings['handler_settings']['sort']['direction'],
          ];
        }
      }
      if (empty($field_settings['handler_settings']['target_bundles'])) {
        $field_settings['handler_settings']['target_bundles'] = NULL;
      }
      $field_settings['handler_settings']['sort'] = $sort;
      $instance_settings['handler_settings'] = $field_settings['handler_settings'];
    }

    if ($row->getSourceProperty('type') == 'node_reference') {
      $instance_settings['handler'] = 'default:node';

      $instance_settings['handler_settings'] = [
        'sort' => [
          'field' => '_none',
          'direction' => 'ASC',
        ],
        'target_bundles' => array_filter($field_data['settings']['referenceable_types'] ?? []),
      ];
    }

    if ($row->getSourceProperty('type') == 'user_reference') {
      $instance_settings['handler'] = 'default:user';

      $instance_settings['handler_settings'] = [
        'include_anonymous' => TRUE,
        'filter' => [
          'type' => '_none',
        ],
        'sort' => [
          'field' => '_none',
          'direction' => 'ASC',
        ],
        'auto_create' => FALSE,
      ];

      if ($row->hasSourceProperty('roles')) {
        $instance_settings['handler_settings']['filter']['type'] = 'role';
        foreach ($row->get('roles') as $role) {
          $instance_settings['handler_settings']['filter']['role'] = [
            $role['name'] => $role['name'],
          ];
        }
      }
    }

    // Get the labels for the list_boolean type.
    if ($row->getSourceProperty('type') === 'list_boolean') {
      if (isset($field_data['settings']['allowed_values'][1])) {
        $instance_settings['on_label'] = $field_data['settings']['allowed_values'][1];
      }
      if (isset($field_data['settings']['allowed_values'][0])) {
        $instance_settings['off_label'] = $field_data['settings']['allowed_values'][0];
      }
    }

    switch ($widget_type) {
      case 'image_image':
      case 'image_miw':
        $settings = $instance_settings;
        $settings['default_image'] = [
          'alt' => '',
          'title' => '',
          'width' => NULL,
          'height' => NULL,
          'uuid' => '',
        ];
        break;

      default:
        $settings = $instance_settings;
    }

    return $settings;
  }

}
