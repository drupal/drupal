<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\migrate\source\d7\File.
 */

namespace Drupal\file\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 file source from database.
 *
 * @MigrateSource(
 *   id = "d7_file"
 * )
 */
class File extends DrupalSqlBase {

  /**
   * The public file directory path.
   *
   * @var string
   */
  protected $publicPath;

  /**
   * The private file directory path, if any.
   *
   * @var string
   */
  protected $privatePath;

  /**
   * The temporary file directory path.
   *
   * @var string
   */
  protected $temporaryPath;

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('file_managed', 'f')
      ->fields('f')
      ->orderBy('timestamp');
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->publicPath = $this->variableGet('file_public_path', 'sites/default/files');
    $this->privatePath = $this->variableGet('file_private_path', NULL);
    $this->temporaryPath = $this->variableGet('file_temporary_path', '/tmp');
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Compute the filepath property, which is a physical representation of
    // the URI relative to the Drupal root.
    $path = str_replace(['public:/', 'private:/', 'temporary:/'], [$this->publicPath, $this->privatePath, $this->temporaryPath], $row->getSourceProperty('uri'));
    // At this point, $path could be an absolute path or a relative path,
    // depending on how the scheme's variable was set. So we need to shear out
    // the source_base_path in order to make them all relative.
    $path = str_replace($this->migration->get('destination.source_base_path'), NULL, $path);
    $row->setSourceProperty('filepath', $path);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'fid' => $this->t('File ID'),
      'uid' => $this->t('The {users}.uid who added the file. If set to 0, this file was added by an anonymous user.'),
      'filename' => $this->t('File name'),
      'filepath' => $this->t('File path'),
      'filemime' => $this->t('File Mime Type'),
      'status' => $this->t('The published status of a file.'),
      'timestamp' => $this->t('The time that the file was added.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }

}
