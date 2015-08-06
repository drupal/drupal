<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\migrate\source\d6\Upload.
 */

namespace Drupal\file\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 upload source from database.
 *
 * @MigrateSource(
 *   id = "d6_upload",
 *   source_provider = "upload"
 * )
 */
class Upload extends DrupalSqlBase {

  /**
   * The join options between the node and the upload table.
   */
  const JOIN = 'n.nid = u.nid AND n.vid = u.vid';

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('upload', 'u')
      ->distinct()
      ->fields('u', array('nid', 'vid'));
    $query->innerJoin('node', 'n', static::JOIN);
    $query->addField('n', 'type');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $query = $this->select('upload', 'u')
      ->fields('u', array('fid', 'description', 'list'))
      ->condition('u.nid', $row->getSourceProperty('nid'))
      ->orderBy('u.weight');
    $query->innerJoin('node', 'n', static::JOIN);
    $row->setSourceProperty('upload', $query->execute()->fetchAll());
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'fid' => $this->t('The file Id.'),
      'nid' => $this->t('The node Id.'),
      'vid' => $this->t('The version Id.'),
      'type' => $this->t('The node type'),
      'description' => $this->t('The file description.'),
      'list' => $this->t('Whether the list should be visible on the node page.'),
      'weight' => $this->t('The file weight.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    $ids['vid']['alias'] = 'u';
    return $ids;
  }

}
