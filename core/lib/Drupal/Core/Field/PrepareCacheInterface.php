<?php

/**
 * @file
 * Contains \Drupal\Core\Field\PrepareCacheInterface.
 */

namespace Drupal\Core\Field;

/**
 * Interface for preparing field values before they enter cache.
 *
 * If a field type implements this interface, this method will be used instead
 * of the regular getValue() to collect the data to include in the cache of
 * field values.
 */
interface PrepareCacheInterface {

  /**
   * Returns the data to store in the field cache.
   *
   * This method is called if the entity type has field caching enabled, when an
   * entity is loaded and no existing cache entry was found in the field cache.
   *
   * This method should never trigger the loading of fieldable entities, since
   * this is likely to cause infinite recursions. A common workaround is to
   * provide a base formatter class implementing the prepareView() method
   * instead.
   *
   * The recommended way to implement it is to provide a computed field item
   * property that can accepts setting a value through setValue(). See
   * \Drupal\text\Plugin\Field\FieldType\TextItemBase and the corresponding
   * computed property Drupal\text\TextProcessed for an example.
   */
  public function getCacheData();

}
