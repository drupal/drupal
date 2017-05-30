<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Taxonomy term source from database.
 *
 * @todo Support term_relation, term_synonym table if possible.
 *
 * @MigrateSource(
 *   id = "d6_taxonomy_term",
 *   source_provider = "taxonomy"
 * )
 */
class Term extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('term_data', 'td')
      ->fields('td')
      ->distinct()
      ->orderBy('td.tid');

    if (isset($this->configuration['bundle'])) {
      $query->condition('td.vid', (array) $this->configuration['bundle'], 'IN');
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
      'name' => $this->t('The name of the term.'),
      'description' => $this->t('The term description.'),
      'weight' => $this->t('Weight'),
      'parent' => $this->t("The Drupal term IDs of the term's parents."),
    ];
    if (isset($this->configuration['translations'])) {
      $fields['language'] = $this->t('The term language.');
      $fields['trid'] = $this->t('Translation ID.');
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Find parents for this row.
    $parents = $this->select('term_hierarchy', 'th')
      ->fields('th', ['parent', 'tid'])
      ->condition('tid', $row->getSourceProperty('tid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('parent', $parents);

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
