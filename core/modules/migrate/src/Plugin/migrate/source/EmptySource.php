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
 * This will return a single row containing 'constants/entity_type' and
 * 'constants/field_name' elements, with values of 'user' and 'image',
 * respectively.
 *
 * For additional configuration keys, refer to the parent class:
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "empty",
 *   source_module = "migrate"
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
  protected function doCount() {
    return 1;
  }

}
