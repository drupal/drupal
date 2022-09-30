<?php

namespace Drupal\file\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 file source from database.
 *
 * Available configuration keys:
 * - site_path: (optional) The path to the site directory relative to Drupal
 *   root. Defaults to 'sites/default'. This value is ignored if the
 *   'file_directory_path' variable is set in the source Drupal database.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: d6_file
 *   site_path: sites/example
 * @endcode
 *
 * In this example, public file values are retrieved from the source database.
 * The site path is specified because it's not the default one (sites/default).
 * The final path to the public files will be "sites/example/files/", assuming
 * the 'file_directory_path' variable is not set in the source database.
 *
 * For complete example, refer to the d6_file.yml migration.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see d6_file.yml
 *
 * @MigrateSource(
 *   id = "d6_file",
 *   source_module = "system"
 * )
 */
class File extends DrupalSqlBase {

  /**
   * The file directory path.
   *
   * @var string
   */
  protected $filePath;

  /**
   * The temporary file path.
   *
   * @var string
   */
  protected $tempFilePath;

  /**
   * Flag for private or public file storage.
   *
   * @var bool
   */
  protected $isPublic;

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('files', 'f')
      ->fields('f')
      ->condition('f.filepath', '/tmp%', 'NOT LIKE')
      ->orderBy('f.timestamp')
      // If two or more files have the same timestamp, they'll end up in a
      // non-deterministic order. Ordering by fid (or any other unique field)
      // will prevent this.
      ->orderBy('f.fid');
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $site_path = $this->configuration['site_path'] ?? 'sites/default';
    $this->filePath = $this->variableGet('file_directory_path', $site_path . '/files') . '/';
    $this->tempFilePath = $this->variableGet('file_directory_temp', '/tmp') . '/';

    // FILE_DOWNLOADS_PUBLIC == 1 and FILE_DOWNLOADS_PRIVATE == 2.
    $this->isPublic = $this->variableGet('file_downloads', 1) == 1;
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('file_directory_path', $this->filePath);
    $row->setSourceProperty('temp_directory_path', $this->tempFilePath);
    $row->setSourceProperty('is_public', $this->isPublic);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('File ID'),
      'uid' => $this->t('The {users}.uid who added the file. If set to 0, this file was added by an anonymous user.'),
      'filename' => $this->t('File name'),
      'filepath' => $this->t('File path'),
      'filemime' => $this->t('File MIME Type'),
      'status' => $this->t('The published status of a file.'),
      'timestamp' => $this->t('The time that the file was added.'),
      'file_directory_path' => $this->t('The Drupal files path.'),
      'is_public' => $this->t('TRUE if the files directory is public otherwise FALSE.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    $ids['fid']['alias'] = 'f';
    return $ids;
  }

}
