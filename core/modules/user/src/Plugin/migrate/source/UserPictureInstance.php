<?php

namespace Drupal\user\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Plugin\migrate\source\DummyQueryTrait;

/**
 * User picture field instance source.
 *
 * @todo Support default picture?
 *
 * @MigrateSource(
 *   id = "user_picture_instance",
 *   source_module = "user"
 * )
 */
class UserPictureInstance extends DrupalSqlBase {

  use DummyQueryTrait;

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \ArrayIterator([
      [
        'id' => '',
        'file_directory' => $this->variableGet('user_picture_path', 'pictures'),
        'max_filesize' => $this->variableGet('user_picture_file_size', '30') . 'KB',
        'max_resolution' => $this->variableGet('user_picture_dimensions', '85x85'),
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'file_directory' => 'The directory to store images..',
      'max_filesize' => 'The maximum allowed file size in KBs.',
      'max_resolution' => "The maximum resolution.",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'string';
    return $ids;
  }

}
