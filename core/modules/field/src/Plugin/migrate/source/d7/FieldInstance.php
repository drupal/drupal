<?php

namespace Drupal\field\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 field instances source from database.
 *
 * @MigrateSource(
 *   id = "d7_field_instance",
 *   source_module = "field"
 * )
 */
class FieldInstance extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config_instance', 'fci')
      ->fields('fci')
      ->fields('fc', ['type', 'translatable'])
      ->condition('fc.active', 1)
      ->condition('fc.storage_active', 1)
      ->condition('fc.deleted', 0)
      ->condition('fci.deleted', 0);
    $query->join('field_config', 'fc', 'fci.field_id = fc.id');

    // Optionally filter by entity type and bundle.
    if (isset($this->configuration['entity_type'])) {
      $query->condition('fci.entity_type', $this->configuration['entity_type']);

      if (isset($this->configuration['bundle'])) {
        $query->condition('fci.bundle', $this->configuration['bundle']);
      }
    }

    // If the Drupal 7 Title module is enabled, we don't want to migrate the
    // fields it provides. The values of those fields will be migrated to the
    // base fields they were replacing.
    if ($this->moduleExists('title')) {
      $title_fields = [
        'title_field',
        'name_field',
        'description_field',
        'subject_field',
      ];
      $query->condition('fc.field_name', $title_fields, 'NOT IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = $this->prepareQuery()->execute()->fetchAll();

    // Group all instances by their base field.
    $instances = [];
    foreach ($results as $result) {
      $instances[$result['field_id']][] = $result;
    }

    // Add the array of all instances using the same base field to each row.
    $rows = [];
    foreach ($results as $result) {
      $result['instances'] = $instances[$result['field_id']];
      $rows[] = $result;
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('The field instance ID.'),
      'field_id' => $this->t('The field ID.'),
      'field_name' => $this->t('The field name.'),
      'entity_type' => $this->t('The entity type.'),
      'bundle' => $this->t('The entity bundle.'),
      'data' => $this->t('The field instance data.'),
      'deleted' => $this->t('Deleted'),
      'type' => $this->t('The field type'),
      'instances' => $this->t('The field instances.'),
      'field_definition' => $this->t('The field definition.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    foreach (unserialize($row->getSourceProperty('data')) as $key => $value) {
      $row->setSourceProperty($key, $value);
    }

    $field_definition = $this->select('field_config', 'fc')
      ->fields('fc')
      ->condition('id', $row->getSourceProperty('field_id'))
      ->execute()
      ->fetch();
    $row->setSourceProperty('field_definition', $field_definition);

    // Determine the translatable setting.
    $translatable = FALSE;
    if ($row->getSourceProperty('entity_type') == 'node') {
      $language_content_type_bundle = (int) $this->variableGet('language_content_type_' . $row->getSourceProperty('bundle'), 0);
      // language_content_type_[bundle] may be
      //   - 0: no language support
      //   - 1: language assignment support
      //   - 2: node translation support
      //   - 4: entity translation support
      if ($language_content_type_bundle === 2 || ($language_content_type_bundle === 4 && $row->getSourceProperty('translatable'))) {
        $translatable = TRUE;
      }
    }
    else {
      // This is not a node entity. Get the translatable value from the source
      // field_config table.
      $field_data = unserialize($field_definition['data']);
      $translatable = $field_data['translatable'];
    }

    // Check if this is an i18n synchronized field.
    $synchronized_fields = $this->variableGet('i18n_sync_node_type_' . $row->getSourceProperty('bundle'), NULL);
    if ($synchronized_fields) {
      if (in_array($row->getSourceProperty('field_name'), $synchronized_fields)) {
        $translatable = FALSE;
      }
    }
    $row->setSourceProperty('translatable', $translatable);

    // Get the vid for each allowed value for taxonomy term reference fields
    // which is used in a migration_lookup in the process pipeline.
    if ($row->getSourceProperty('type') == 'taxonomy_term_reference') {
      $vocabulary = [];
      $data = unserialize($field_definition['data']);
      foreach ($data['settings']['allowed_values'] as $allowed_value) {
        $vocabulary[] = $allowed_value['vocabulary'];
      }
      $query = $this->select('taxonomy_vocabulary', 'v')
        ->fields('v', ['vid'])
        ->condition('machine_name', $vocabulary, 'IN');
      $allowed_vid = $query->execute()->fetchAllAssoc('vid');
      $row->setSourceProperty('allowed_vid', $allowed_vid);

      // If there is an i18n_mode use it to determine if this field is
      // translatable. It is TRUE for i18n_modes 'Vocab Fixed' and  'Translate',
      // for all others it is FALSE. When there is a term reference field with
      // two vocabularies where one vocabulary is translatable and other is not
      // the field itself is set to not translatable. Note mode '5' is not used
      // for taxonomy but is listed here for completeness.
      // - 0: No multilingual options.
      // - 1: Localize. Localizable object.
      // - 2: Fixed Language.
      // - 4: Translate. Multilingual objects.
      // - 5: Objects are translatable, if they have language or localizable
      // if not)
      if ($this->getDatabase()
        ->schema()
        ->fieldExists('taxonomy_vocabulary', 'i18n_mode')) {
        $query = $this->select('taxonomy_vocabulary', 'v')
          ->fields('v', ['i18n_mode'])
          ->condition('machine_name', $vocabulary, 'IN');
        $results = $query->execute()->fetchAllAssoc('i18n_mode');
        $translatable = FALSE;
        foreach ($results as $result) {
          if ($result['i18n_mode'] == '2' || $result['i18n_mode'] == '4') {
            $translatable = TRUE;
          }
        }
        $row->setSourceProperty('translatable', $translatable);
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type' => [
        'type' => 'string',
        'alias' => 'fci',
      ],
      'bundle' => [
        'type' => 'string',
        'alias' => 'fci',
      ],
      'field_name' => [
        'type' => 'string',
        'alias' => 'fci',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    return $this->initializeIterator()->count();
  }

}
