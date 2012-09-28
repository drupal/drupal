<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\ContextAwareInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for context aware data.
 */
interface ContextAwareInterface {

  /**
   * Returns the name of a property or item.
   *
   * @return string
   *   If the data is a property of some complex data, the name of the property.
   *   If the data is an item of a list, the name is the numeric position of the
   *   item in the list, starting with 0. Otherwise, NULL is returned.
   */
  public function getName();

  /**
   * Sets the name of a property or item.
   *
   * This method is supposed to be used by the parental data structure in order
   * to provide appropriate context only.
   *
   * @param string $name
   *   The name to set for a property or item.
   *
   * @see ContextAwareInterface::getName()
   */
  public function setName($name);

  /**
   * Returns the parent data structure; i.e. either complex data or a list.
   *
   * @return Drupal\Core\TypedData\ComplexDataInterface|Drupal\Core\TypedData\ListInterface
   *   The parent data structure; either complex data or a list.
   */
  public function getParent();

  /**
   * Sets the parent of a property or item.
   *
   * This method is supposed to be used by the parental data structure in order
   * to provide appropriate context only.
   *
   * @param mixed $parent
   *   The parent data structure; either complex data or a list.
   *
   * @see ContextAwareInterface::getParent()
   */
  public function setParent($parent);
}
