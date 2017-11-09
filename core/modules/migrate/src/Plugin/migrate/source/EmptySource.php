<?php

namespace Drupal\migrate\Plugin\migrate\source;

/**
 * Source returning a row based on the constants provided.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: empty
 *   constants:
 *     entity_type: user
 *     field_name: image
 * @endcode
 *
 * This will return a single row containing 'entity_type' and 'field_name'
 * elements, with values of 'user' and 'image', respectively.
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
    return [
      'id' => t('ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \ArrayIterator([['id' => '']]);
  }

  /**
   * Allows class to decide how it will react when it is treated like a string.
   */
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
