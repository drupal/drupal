<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\UploadInstance.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 upload instance source from database.
 *
 * @MigrateSource(
 *   id = "d6_upload_instance",
 *   source_provider = "upload"
 * )
 */
class UploadInstance extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $prefix = 'upload';
    $node_types = $this->getDatabase()->query('SELECT type FROM {node_type}')->fetchCol();
    foreach ($node_types as $node_type) {
      $variables[] = $prefix . '_' . $node_type;
    }

    $max_filesize = $this->variableGet('upload_uploadsize_default', 1);
    $max_filesize = $max_filesize ? $max_filesize . 'MB' : '';
    $file_extensions = $this->variableGet('upload_extensions_default', 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp');
    $return = array();
    $values = $this->getDatabase()->query('SELECT name, value FROM {variable} WHERE name IN ( :name[] )', array(':name[]' => $variables))->fetchAllKeyed();
    foreach ($node_types as $node_type) {
      $name = $prefix . '_' . $node_type;
      if (isset($values[$name])) {
        $enabled = unserialize($values[$name]);
        if ($enabled) {
          $return[$node_type]['node_type'] = $node_type;
          $return[$node_type]['max_filesize'] = $max_filesize;
          $return[$node_type]['file_extensions'] = $file_extensions;
        }
      }
    }

    return new \ArrayIterator($return);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'node_type' => array(
        'type' => 'string',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Nothing needed here.
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'node_type' => $this->t('Node type'),
      'max_filesize' => $this->t('Max filesize'),
      'file_extensions' => $this->t('File extensions'),
    );
  }

}
