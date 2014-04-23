<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\CckFieldRevision.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

/**
 * Drupal 6 cck field revision source.
 *
 * @MigrateSource(
 *   id = "d6_cck_field_revision"
 * )
 */
class CckFieldRevision extends CckFieldValues {

  /**
   * The join options between the node and the node_revisions_table.
   */
  const JOIN = 'n.nid = nr.nid AND n.vid <> nr.vid';

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Use all the node fields plus the vid that identifies the version.
    return parent::fields() + array('vid' => t('The primary identifier for this version.'));
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    $ids['vid']['alias'] = 'nr';
    return $ids;
  }

}
