<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\UserPictureFile.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Drupal 6 user picture source from database.
 *
 * @MigrateSource(
 *   id = "d6_user_picture_file"
 * )
 */
class UserPictureFile extends DrupalSqlBase {

  /**
   * The file directory path.
   *
   * @var string
   */
  protected $filePath;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('users', 'u')
      ->condition('picture', '', '<>')
      ->fields('u', array('uid', 'picture'));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function runQuery() {
    $conf_path = isset($this->configuration['conf_path']) ? $this->configuration['conf_path'] : 'sites/default';
    $this->filePath = $this->variableGet('file_directory_path', $conf_path . '/files') . '/';
    return parent::runQuery();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('filename', basename($row->getSourceProperty('picture')));
    $row->setSourceProperty('file_directory_path', $this->filePath);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'picture' => "Path to the user's uploaded picture.",
      'filename' => 'The picture filename.',
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['uid']['type'] = 'integer';
    return $ids;
  }

}
