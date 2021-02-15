<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Provides Drupal 7 taxonomy term entity translation source plugin.
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
 * @MigrateSource(
 *   id = "d7_taxonomy_term_entity_translation",
 *   source_module = "entity_translation"
 * )
 */
class TermEntityTranslation extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('entity_translation', 'et')
      ->fields('et')
      ->fields('td', [
        'name',
        'description',
        'format',
      ])
      ->fields('tv', [
        'machine_name',
      ])
      ->condition('et.entity_type', 'taxonomy_term')
      ->condition('et.source', '', '<>');

    $query->innerJoin('taxonomy_term_data', 'td', 'td.tid = et.entity_id');
    $query->innerJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');

    if (isset($this->configuration['bundle'])) {
      $query->condition('tv.machine_name', (array) $this->configuration['bundle'], 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $tid = $row->getSourceProperty('entity_id');
    $vocabulary = $row->getSourceProperty('machine_name');
    $language = $row->getSourceProperty('language');

    // Get Field API field values.
    foreach ($this->getFields('taxonomy_term', $vocabulary) as $field_name => $field) {
      // Ensure we're using the right language if the entity is translatable.
      $field_language = $field['translatable'] ? $language : NULL;
      $row->setSourceProperty($field_name, $this->getFieldValues('taxonomy_term', $field_name, $tid, NULL, $field_language));
    }

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

    // Determine if this is a forum container.
    $forum_container_tids = $this->variableGet('forum_containers', []);
    $row->setSourceProperty('is_container', in_array($tid, $forum_container_tids));

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'entity_type' => $this->t('The entity type this translation relates to'),
      'entity_id' => $this->t('The entity ID this translation relates to'),
      'revision_id' => $this->t('The entity revision ID this translation relates to'),
      'language' => $this->t('The target language for this translation.'),
      'source' => $this->t('The source language from which this translation was created.'),
      'uid' => $this->t('The author of this translation.'),
      'status' => $this->t('Boolean indicating whether the translation is published (visible to non-administrators).'),
      'translate' => $this->t('A boolean indicating whether this translation needs to be updated.'),
      'created' => $this->t('The Unix timestamp when the translation was created.'),
      'changed' => $this->t('The Unix timestamp when the translation was most recently saved.'),
      'name' => $this->t('The name of the term.'),
      'description' => $this->t('The term description.'),
      'format' => $this->t('Format of the term description.'),
      'machine_name' => $this->t('Vocabulary machine name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_id' => [
        'type' => 'integer',
        'alias' => 'et',
      ],
      'language' => [
        'type' => 'string',
        'alias' => 'et',
      ],
    ];
  }

}
