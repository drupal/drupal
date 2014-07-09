<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\CckFieldValues.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate\Plugin\SourceEntityInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;


/**
 * Drupal 6 cck field source.
 *
 * @MigrateSource(
 *   id = "d6_cck_field_values"
 * )
 */
class CckFieldValues extends DrupalSqlBase implements SourceEntityInterface {

  /**
   * The join options between the node and the node_revisions table.
   */
  const JOIN = 'n.vid = nr.vid';

  /**
   * The source field information for complex node fields.
   *
   * @var array
   */
  protected $sourceFieldInfo;

  /**
   * Information on which tables exist.
   *
   * @var array
   */
  protected $tables;

  /**
   * TRUE when CCK is enabled and the schema is correct.
   *
   * @var bool
   */
  protected $cckSchemaCorrect;

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select node in its last revision.
    $query = $this->select('node_revisions', 'nr')
      ->fields('n', array(
        'nid',
        'type',
      ))
      ->fields('nr', array(
        'vid',
      ))
      ->condition('type', $this->configuration['bundle']);
    $query->innerJoin('node', 'n', static::JOIN);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $bundle = $row->getSourceProperty('type');
    // Pick up simple CCK fields.
    $cck_table = "content_type_$bundle";
    if ($this->tableExists($cck_table)) {
      $query = $this->select($cck_table, 'f')->condition('vid', $row->getSourceProperty('vid'));
      // The main column for the field should be rendered with the field name,
      // not the column name (e.g., field_foo rather than field_foo_value).
      $field_info = $this->getSourceFieldInfo($bundle);
      foreach ($field_info as $field_name => $info) {
        if (isset($info['columns']) && !$info['multiple'] && $info['db_storage']) {
          $i = 0;
          $data = FALSE;
          foreach ($info['columns'] as $display_name => $column_name) {
            if ($i++ == 0) {
              $query->addField('f', $column_name, $field_name);
            }
            else {
              // The database API won't allow colons in column aliases, so we
              // will accept the default alias, and fix up the field names later.
              // Remember how to translate the field names.
              if ($info['type'] == 'filefield' &&
                (strpos($display_name, ':list') || strpos($display_name, ':description'))) {
                if (!$data) {
                  //$this->fileDataFields[] = $field_name . '_data';
                  $query->addField('f', $field_name . '_data');
                  $data = TRUE;
                }
              }
              else {
                $query->addField('f', $column_name);
              }
            }
          }
        }
      }
      if ($results = $query->execute()->fetchAssoc()) {
        $source = $row->getSource();
        // We diff the results because the extra will be all the field columns.
        $new_fields = array_diff($results, $source);
        foreach ($new_fields as $key => $value) {
          $row->setSourceProperty($key, $value);
        }
      }
    }

    // Handle fields that have their own table.
    foreach ($this->getSourceFieldInfo($bundle) as $field_name => $field_info) {
      if ($field_info['multiple'] || !$field_info['db_storage']) {
        // Select the data.
        $table = "content_$field_name";
        $field_query = $this
          ->select($table, 't')
          ->condition('vid', $row->getSourceProperty('vid'));
        if ($field_info['multiple']) {
          $field_query->addField('t', 'delta');
        }
        $data = FALSE;
        foreach ($field_info['columns'] as $display_name => $column_name) {
          // The database API won't allow colons in column aliases, so we
          // will accept the default alias, and fix up the field names later.
          // Remember how to translate the field names.
          if ($field_info['type'] == 'filefield' &&
            (strpos($display_name, ':list') || strpos($display_name, ':description'))) {
            if (!$data) {
              //$this->fileDataFields[] = $field_name . '_data';
              $field_query->addField('t', $field_name . '_data');
              $data = TRUE;
            }
          }
          else {
            $field_query->addField('t', $column_name);
          }
        }

        if ($field_info['multiple']) {
          foreach ($field_query->execute() as $field_row) {
            foreach ($field_info['columns'] as $display_name => $column_name) {
              list ( , $column) = explode(':', $display_name);
              $property_path = $field_name . Row::PROPERTY_SEPARATOR . $field_row['delta'] . Row::PROPERTY_SEPARATOR . $column;
              $row->setSourceProperty($property_path, $field_row[$column_name]);
            }
          }
        }
        else {
          if ($field_row = $field_query->execute()->fetchAssoc()) {
            foreach ($field_info['columns'] as $display_name => $column_name) {
              $row->setSourceProperty(str_replace(':', Row::PROPERTY_SEPARATOR, $display_name), $field_row[$column_name]);
            }
          }
        }
      }
    }
    parent::prepareRow($row);
  }

  /**
   * Get all the complex field info.
   *
   * @param string $bundle
   *   The bundle for which fields we want.
   *
   * @return array
   *   An array of field info keyed by field name.
   */
  protected function getSourceFieldInfo($bundle) {
    if (!isset($this->sourceFieldInfo)) {
      $this->sourceFieldInfo = array();
      if ($this->tableExists('content_node_field_instance')) {
        // Get each field attached to this type.
        $query = $this->select('content_node_field_instance', 'i')
          ->fields('i', array(
            'label',
            'widget_settings',
            'field_name',
          ))
          ->condition('type_name', $bundle);

        $query->innerJoin('content_node_field', 'f', 'i.field_name = f.field_name');
        $query->fields('f', array(
            'field_name',
            'type',
            'db_columns',
            'global_settings',
            'multiple',
            'db_storage')
        );

        $results = $query->execute();
        foreach ($results as $row) {
          $field_name = trim($row['field_name']);
          $db_columns = $db_columns = !empty($row['db_columns']) ? unserialize($row['db_columns']) : array();
          $columns = array();
          foreach ($db_columns as $column_name => $column_info) {
            // Special handling for the stuff packed into filefield's "data"
            if ($row['type'] == 'filefield' && $column_name == 'data') {
              $widget_settings = unserialize($row['widget_settings']);
              $global_settings = unserialize($row['global_settings']);

              if (!empty($widget_settings['custom_alt'])) {
                $columns[$field_name . ':alt'] = $field_name . '_alt';
              }
              if (!empty($widget_settings['custom_title'])) {
                $columns[$field_name . ':title'] = $field_name . '_title';
              }
              if (!empty($global_settings['description_field'])) {
                $columns[$field_name . ':description'] = $field_name . '_description';
              }
            }
            else {
              $display_name = $field_name . ':' . $column_name;
              $column_name = $field_name . '_' . $column_name;
              $columns[$display_name] = $column_name;
            }
          }
          $this->sourceFieldInfo[$field_name] = array(
            'label' => $row['label'],
            'type' => $row['type'],
            'columns' => $columns,
            'multiple' => $row['multiple'],
            'db_storage' => $row['db_storage'],
            'bundle' => $bundle,
          );
        }
      }
    }

    return $this->sourceFieldInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'nid' => $this->t('Node ID'),
      'type' => $this->t('Type'),
    );
    foreach ($this->getSourceFieldInfo($this->configuration['bundle']) as $field_name => $field_data) {
      $fields[$field_name] = $field_data['label'];
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    $ids['nid']['alias'] = 'n';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function bundleMigrationRequired() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'node';
  }

  /**
   * Determines whether a specific CCK table exists.
   */
  protected function tableExists($table) {
    if (!isset($this->tables[$table])) {
      $this->tables[$table] = $this->cckSchemaCorrect() && $this->getDatabase()->schema()->tableExists($table);
    }
    return $this->tables[$table];
  }

  /**
   * Determines whether CCK is enabled and is using the right schema.
   */
  protected function cckSchemaCorrect() {
    if (!isset($this->cckSchemaCorrect)) {
      $this->cckSchemaCorrect = $this->moduleExists('content') && $this->getModuleSchemaVersion('content') >= 6001;
    }
    return $this->cckSchemaCorrect;
  }

}
