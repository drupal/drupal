<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\Constants.
 */

namespace Drupal\migrate\Plugin\migrate\source;

/**
 * Source returning an empty row.
 *
 * This is generally useful when needing to create a field using a migration..
 *
 * @MigrateSource(
 *   id = "empty"
 * )
 */
class EmptySource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'id' => t('ID'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \ArrayIterator(array(array('id' => '')));
  }

  public function __toString() {
    return '';
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
  public function count() {
    return 1;
  }

}
