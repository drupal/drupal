<?php

namespace Drupal\book\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 and 7 book source.
 *
 * @MigrateSource(
 *   id = "book",
 *   source_module = "book",
 * )
 */
class Book extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('book', 'b')->fields('b', ['nid', 'bid']);
    $query->join('menu_links', 'ml', '[b].[mlid] = [ml].[mlid]');
    $ml_fields = ['mlid', 'plid', 'weight', 'has_children', 'depth'];
    foreach (range(1, 9) as $i) {
      $field = "p$i";
      $ml_fields[] = $field;
      $query->orderBy('ml.' . $field);
    }
    return $query->fields('ml', $ml_fields);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['mlid']['type'] = 'integer';
    $ids['mlid']['alias'] = 'ml';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('Node ID'),
      'bid' => $this->t('Book ID'),
      'mlid' => $this->t('Menu link ID'),
      'plid' => $this->t('Parent link ID'),
      'weight' => $this->t('Weight'),
      'p1' => $this->t('The first mlid in the materialized path. If N = depth, then pN must equal the mlid. If depth > 1 then p(N-1) must equal the parent link mlid. All pX where X > depth must equal zero. The columns p1 .. p9 are also called the parents.'),
      'p2' => $this->t('The second mlid in the materialized path. See p1.'),
      'p3' => $this->t('The third mlid in the materialized path. See p1.'),
      'p4' => $this->t('The fourth mlid in the materialized path. See p1.'),
      'p5' => $this->t('The fifth mlid in the materialized path. See p1.'),
      'p6' => $this->t('The sixth mlid in the materialized path. See p1.'),
      'p7' => $this->t('The seventh mlid in the materialized path. See p1.'),
      'p8' => $this->t('The eighth mlid in the materialized path. See p1.'),
      'p9' => $this->t('The ninth mlid in the materialized path. See p1.'),
    ];
  }

}
