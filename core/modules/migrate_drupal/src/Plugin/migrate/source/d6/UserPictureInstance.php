<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\UserPictureInstance.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 user picture field instance source.
 *
 * @todo Support default picture?
 *
 * @MigrateSource(
 *   id = "d6_user_picture_instance"
 * )
 */
class UserPictureInstance extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \ArrayIterator(array(
      array(
        'id' => '',
        'file_directory' => $this->variableGet('user_picture_path', 'pictures'),
        'max_filesize' => $this->variableGet('user_picture_file_size', '30') . 'KB',
        'max_resolution' => $this->variableGet('user_picture_dimensions', '85x85'),
      )));
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'file_directory' => 'The directory to store images..',
      'max_filesize' => 'The maximum allowed file size in KBs.',
      'max_resolution' => "The maximum resolution.",
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Nothing to do here.
  }

}
