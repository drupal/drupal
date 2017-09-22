<?php

namespace Drupal\file\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Plugin\migrate\source\DummyQueryTrait;

/**
 * Drupal 6 upload instance source from database.
 *
 * @MigrateSource(
 *   id = "d6_upload_instance",
 *   source_module = "upload"
 * )
 */
class UploadInstance extends DrupalSqlBase {

  use DummyQueryTrait;

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $node_types = $this->select('node_type', 'nt')
      ->fields('nt', ['type'])
      ->execute()
      ->fetchCol();
    $variables = array_map(function($type) {
      return 'upload_' . $type;
    }, $node_types);

    $max_filesize = $this->variableGet('upload_uploadsize_default', 1);
    $max_filesize = $max_filesize ? $max_filesize . 'MB' : '';
    $file_extensions = $this->variableGet('upload_extensions_default', 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp');
    $return = [];
    $values = $this->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('v.name', $variables, 'IN')
      ->execute()
      ->fetchAllKeyed();
    foreach ($node_types as $node_type) {
      $name = 'upload_' . $node_type;
      // By default, file attachments in D6 are enabled unless upload_<type> is
      // false, so include types where the upload-variable is not set.
      $enabled = !isset($values[$name]) || unserialize($values[$name]);
      if ($enabled) {
        $return[$node_type]['node_type'] = $node_type;
        $return[$node_type]['max_filesize'] = $max_filesize;
        $return[$node_type]['file_extensions'] = $file_extensions;
      }
    }

    return new \ArrayIterator($return);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'node_type' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'node_type' => $this->t('Node type'),
      'max_filesize' => $this->t('Max filesize'),
      'file_extensions' => $this->t('File extensions'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->initializeIterator());
  }

}
