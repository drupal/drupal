<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\Term.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 taxonomy terms source from database.
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
    // Note the explode - this supports the (admittedly unusual) case of
    // consolidating multiple vocabularies into one.
    $query = $this->select('term_data', 'td')
      ->fields('td', array('tid', 'vid', 'name', 'description', 'weight'))
    // This works, but we cannot test that, because there is no support for
    // distinct() in FakeSelect, yet.
      ->distinct()
      ->orderBy('tid');
    if (isset($this->configuration['vocabulary'])) {
      $query->condition('vid', $this->configuration['vocabulary'], 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'tid' => $this->t('The term ID.'),
      'vid' => $this->t('Existing term VID'),
      'name' => $this->t('The name of the term.'),
      'description' => $this->t('The term description.'),
      'weight' => $this->t('Weight'),
      'parent' => $this->t("The Drupal term IDs of the term's parents."),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Find parents for this row.
    $parents = $this->select('term_hierarchy', 'th')
      ->fields('th', array('parent', 'tid'))
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
