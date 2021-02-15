<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Taxonomy term source from database.
 *
 * Available configuration keys:
 * - bundle: (optional) The taxonomy vocabulary (machine name) to filter terms
 *   retrieved from the source - can be a string or an array. If omitted, all
 *   terms are retrieved.
 *
 * Examples:
 *
 * @code
 * source:
 *   plugin: d7_taxonomy_term
 *   bundle: tags
 * @endcode
 *
 * In this example terms of 'tags' vocabulary are retrieved from the source
 * database.
 *
 * @code
 * source:
 *   plugin: d7_taxonomy_term
 *   bundle: [tags, forums]
 * @endcode
 *
 * In this example terms of 'tags' and 'forums' vocabularies are retrieved
 * from the source database.
 *
 * For additional configuration keys, refer to the parent classes:
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @todo Support term_relation, term_synonym table if possible.
 *
 * @MigrateSource(
 *   id = "d7_taxonomy_term",
 *   source_module = "taxonomy"
 * )
 */
class Term extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('taxonomy_term_data', 'td')
      ->fields('td')
      ->distinct()
      ->orderBy('tid');
    $query->leftJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');
    $query->addField('tv', 'machine_name');

    if ($this->getDatabase()
      ->schema()
      ->fieldExists('taxonomy_vocabulary', 'i18n_mode')) {
      $query->addField('tv', 'i18n_mode');
    }

    if (isset($this->configuration['bundle'])) {
      $query->condition('tv.machine_name', (array) $this->configuration['bundle'], 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'tid' => $this->t('The term ID.'),
      'vid' => $this->t('Existing term VID'),
      'machine_name' => $this->t('Vocabulary machine name'),
      'name' => $this->t('The name of the term.'),
      'description' => $this->t('The term description.'),
      'weight' => $this->t('Weight'),
      'parent' => $this->t("The Drupal term IDs of the term's parents."),
      'format' => $this->t("Format of the term description."),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $tid = $row->getSourceProperty('tid');
    $vocabulary = $row->getSourceProperty('machine_name');
    $default_language = (array) $this->variableGet('language_default', ['language' => 'en']);

    // If this entity was translated using Entity Translation, we need to get
    // its source language to get the field values in the right language.
    // The translations will be migrated by the d7_node_entity_translation
    // migration.
    $translatable_vocabularies = array_keys(array_filter($this->variableGet('entity_translation_taxonomy', [])));
    $entity_translatable = $this->isEntityTranslatable('taxonomy_term') && in_array($vocabulary, $translatable_vocabularies, TRUE);

    if ($entity_translatable) {
      $source_language = $this->getEntityTranslationSourceLanguage('taxonomy_term', $tid);
      $language = $entity_translatable && $source_language ? $source_language : $default_language['language'];
    }
    // If this is an i18n translation use the default language when i18n_mode
    // is localized.
    if ($row->get('i18n_mode')) {
      $language = ($row->get('i18n_mode') === '1') ? $default_language['language'] : $row->get('language');
    }

    $language = $language ?? $default_language['language'];
    $row->setSourceProperty('language', $language);

    // Get Field API field values.
    foreach ($this->getFields('taxonomy_term', $vocabulary) as $field_name => $field) {
      // Ensure we're using the right language if the entity and the field are
      // translatable.
      $field_language = $entity_translatable && $field['translatable'] ? $language : NULL;
      $row->setSourceProperty($field_name, $this->getFieldValues('taxonomy_term', $field_name, $tid, NULL, $field_language));
    }

    // Find parents for this row.
    $parents = $this->select('taxonomy_term_hierarchy', 'th')
      ->fields('th', ['parent', 'tid'])
      ->condition('tid', $row->getSourceProperty('tid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('parent', $parents);

    // Determine if this is a forum container.
    $forum_container_tids = $this->variableGet('forum_containers', []);
    $current_tid = $row->getSourceProperty('tid');
    $row->setSourceProperty('is_container', in_array($current_tid, $forum_container_tids));

    // If the term name or term description were replaced by real fields using
    // the Drupal 7 Title module, use the fields value instead of the term name
    // or term description.
    if ($this->moduleExists('title')) {
      $name_field = $row->getSourceProperty('name_field');
      if (isset($name_field[0]['value'])) {
        $row->setSourceProperty('name', $name_field[0]['value']);
      }
      $description_field = $row->getSourceProperty('description_field');
      if (isset($description_field[0]['value'])) {
        $row->setSourceProperty('description', $description_field[0]['value']);
      }
      if (isset($description_field[0]['format'])) {
        $row->setSourceProperty('format', $description_field[0]['format']);
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['tid']['type'] = 'integer';
    return $ids;
  }

}
