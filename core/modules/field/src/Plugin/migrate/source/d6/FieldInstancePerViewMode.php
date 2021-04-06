<?php

namespace Drupal\field\Plugin\migrate\source\d6;

use Drupal\node\Plugin\migrate\source\d6\ViewModeBase;

/**
 * Drupal 6 field instance per view mode source from database.
 *
 * For available configuration keys, refer to the parent classes:
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_field_instance_per_view_mode",
 *   source_module = "content"
 * )
 */
class FieldInstancePerViewMode extends ViewModeBase {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $rows = [];
    $result = $this->prepareQuery()->execute();
    while ($field_row = $result->fetchAssoc()) {
      // These are added to every view mode row.
      $field_row['display_settings'] = unserialize($field_row['display_settings']);
      $field_row['widget_settings'] = unserialize($field_row['widget_settings']);
      $bundle = $field_row['type_name'];
      $field_name = $field_row['field_name'];

      foreach ($this->getViewModes() as $view_mode) {
        // Append to the return value if the row has display settings for this
        // view mode and the view mode is neither hidden nor excluded.
        // @see \Drupal\node\Plugin\migrate\source\d6\ViewMode::initializeIterator()
        if (isset($field_row['display_settings'][$view_mode]) && $field_row['display_settings'][$view_mode]['format'] != 'hidden' && empty($field_row['display_settings'][$view_mode]['exclude'])) {
          $index = $view_mode . "." . $bundle . "." . $field_name;
          $rows[$index]['entity_type'] = 'node';
          $rows[$index]['view_mode'] = $view_mode;
          $rows[$index]['type_name'] = $bundle;
          $rows[$index]['field_name'] = $field_name;
          $rows[$index]['type'] = $field_row['type'];
          $rows[$index]['module'] = $field_row['module'];
          $rows[$index]['weight'] = $field_row['weight'];
          $rows[$index]['label'] = $field_row['display_settings']['label']['format'];
          $rows[$index]['display_settings'] = $field_row['display_settings'][$view_mode];
          $rows[$index]['widget_settings'] = $field_row['widget_settings'];
        }
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('content_node_field_instance', 'cnfi')
      ->fields('cnfi', [
        'field_name',
        'type_name',
        'weight',
        'label',
        'display_settings',
        'widget_settings',
      ])
      ->fields('cnf', [
        'type',
        'module',
      ]);
    $query->join('content_node_field', 'cnf', 'cnfi.field_name = cnf.field_name');
    $query->orderBy('cnfi.weight');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => $this->t('The machine name of field.'),
      'type_name' => $this->t('Content type where this field is used.'),
      'weight' => $this->t('Weight.'),
      'label' => $this->t('A name to show.'),
      'widget_type' => $this->t('Widget type.'),
      'widget_settings' => $this->t('Serialize data with widget settings.'),
      'display_settings' => $this->t('Serialize data with display settings.'),
      'description' => $this->t('A description of field.'),
      'widget_module' => $this->t('Module that implements widget.'),
      'widget_active' => $this->t('Status of widget'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type_name']['type'] = 'string';
    $ids['view_mode']['type'] = 'string';
    $ids['entity_type']['type'] = 'string';
    $ids['field_name']['type'] = 'string';
    return $ids;
  }

}
