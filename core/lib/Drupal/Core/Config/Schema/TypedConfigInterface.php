<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\TypedConfigInterface.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Interface for a typed configuration object that contains multiple elements.
 *
 * A list of typed configuration contains any number of items whose type
 * will depend on the configuration schema but also on the configuration
 * data being parsed.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 */
interface TypedConfigInterface extends TraversableTypedDataInterface {

  /**
   * Determines whether the data structure is empty.
   *
   * @return boolean
   *   TRUE if the data structure is empty, FALSE otherwise.
   */
  public function isEmpty();

  /**
   * Gets an array of contained elements.
   *
   * @return array
   *   Array of \Drupal\Core\TypedData\TypedDataInterface objects.
   */
  public function getElements();

  /**
   * Gets a contained typed configuration element.
   *
   * @param $name
   *   The name of the property to get; e.g., 'title' or 'name'. Nested
   *   elements can be get using multiple dot delimited names, for example,
   *   'page.front'.
   *
   * @throws \InvalidArgumentException
   *   If an invalid property name is given.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The property object.
   */
  public function get($name);

  /**
   * Replaces the item at the specified position in this list.
   *
   * @param int|string $key
   *   Property name or index of the item to replace.
   * @param mixed $value
   *   Value to be stored at the specified position.
   *
   * @return $this
   */
  public function set($key, $value);

  /**
   * Returns an array of all property values.
   *
   * @return array
   *   An array of property values, keyed by property name.
   */
  public function toArray();

}
