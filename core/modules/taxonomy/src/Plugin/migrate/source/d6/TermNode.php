<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 term/node relationships (current revision) source from database.
 *
 * Available configuration keys:
 * - vid: (optional) The taxonomy vocabulary (vid) to filter terms retrieved
 *   from the source - should be an integer. If omitted, all terms are
 *   retrieved.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: d6_term_node
 *   vid: 7
 * @endcode
 *
 * In this example the relations between nodes and terms are retrieved from
 * the source database. Source rows include only terms that belong to the
 * vocabulary with 'vid' equal to 7.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_term_node",
 *   source_module = "taxonomy"
 * )
 */
class TermNode extends DrupalSqlBase {

  /**
   * The join options between the node and the term node table.
   */
  const JOIN = '[tn].[vid] = [n].[vid]';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('term_node', 'tn')
      ->distinct()
      ->fields('tn', ['nid', 'vid'])
      ->fields('n', ['type']);
    // Because this is an inner join it enforces the current revision.
    $query->innerJoin('term_data', 'td', '[td].[tid] = [tn].[tid] AND [td].[vid] = :vid', [':vid' => $this->configuration['vid']]);
    $query->innerJoin('node', 'n', static::JOIN);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('The node revision ID.'),
      'vid' => $this->t('The node revision ID.'),
      'tid' => $this->t('The term ID.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Select the terms belonging to the revision selected.
    $query = $this->select('term_node', 'tn')
      ->fields('tn', ['tid'])
      ->condition('n.nid', $row->getSourceProperty('nid'));
    $query->join('node', 'n', static::JOIN);
    $query->innerJoin('term_data', 'td', '[td].[tid] = [tn].[tid] AND [td].[vid] = :vid', [':vid' => $this->configuration['vid']]);
    $row->setSourceProperty('tid', $query->execute()->fetchCol());
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    $ids['vid']['alias'] = 'tn';
    return $ids;
  }

}
