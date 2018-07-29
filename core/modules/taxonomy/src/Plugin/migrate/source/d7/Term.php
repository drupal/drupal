<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Taxonomy term source from database.
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
    // Get Field API field values.
    foreach (array_keys($this->getFields('taxonomy_term', $row->getSourceProperty('machine_name'))) as $field) {
      $tid = $row->getSourceProperty('tid');
      $row->setSourceProperty($field, $this->getFieldValues('taxonomy_term', $field, $tid));
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
