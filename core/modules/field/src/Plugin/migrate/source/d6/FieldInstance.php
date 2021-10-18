<?php

namespace Drupal\field\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

// cspell:ignore nodeapi

/**
 * Drupal 6 field instances source from database.
 *
 * Available configuration keys:
 * - node_type: (optional) The content type (machine name) to filter field
 *   instances retrieved from the source. If omitted, all field instances are
 *   retrieved.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: d6_field_instance
 *   node_type: page
 * @endcode
 *
 * In this example field instances of type page are retrieved from the source
 * database.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_field_instance",
 *   source_module = "content"
 * )
 */
class FieldInstance extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('content_node_field_instance', 'cnfi')->fields('cnfi');
    if (isset($this->configuration['node_type'])) {
      $query->condition('cnfi.type_name', $this->configuration['node_type']);
    }
    $query->join('content_node_field', 'cnf', '[cnf].[field_name] = [cnfi].[field_name]');
    $query->fields('cnf');
    $query->orderBy('cnfi.field_name');
    $query->orderBy('cnfi.type_name');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => $this->t('The machine name of field.'),
      'type_name' => $this->t('Content type where this field is in use.'),
      'weight' => $this->t('Weight.'),
      'label' => $this->t('A name to show.'),
      'widget_type' => $this->t('Widget type.'),
      'widget_settings' => $this->t('Serialize data with widget settings.'),
      'display_settings' => $this->t('Serialize data with display settings.'),
      'description' => $this->t('A description of field.'),
      'widget_module' => $this->t('Module that implements widget.'),
      'widget_active' => $this->t('Status of widget'),
      'module' => $this->t('The module that provides the field.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Unserialize data.
    $widget_settings = unserialize($row->getSourceProperty('widget_settings'));
    $display_settings = unserialize($row->getSourceProperty('display_settings'));
    $global_settings = unserialize($row->getSourceProperty('global_settings'));
    $row->setSourceProperty('widget_settings', $widget_settings);
    $row->setSourceProperty('display_settings', $display_settings);
    $row->setSourceProperty('global_settings', $global_settings);

    // Determine the translatable setting.
    $translatable = TRUE;
    $synchronized_fields = $this->variableGet('i18nsync_nodeapi_' . $row->getSourceProperty('type_name'), NULL);
    if ($synchronized_fields) {
      if (in_array($row->getSourceProperty('field_name'), $synchronized_fields)) {
        $translatable = FALSE;
      }
    }
    $row->setSourceProperty('translatable', $translatable);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [
      'field_name' => [
        'type' => 'string',
        'alias' => 'cnfi',
      ],
      'type_name' => [
        'type' => 'string',
      ],
    ];
    return $ids;
  }

}
