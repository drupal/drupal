<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\ContextAwareTypedData.
 */

namespace Drupal\Core\TypedData;

/**
 * An abstract base class for context aware typed data.
 *
 * This implementation requires parent typed data objects to implement the
 * ContextAwareInterface also, such that the context can be derived from the
 * parent.
 *
 * Classes deriving from this base class have to declare $value
 * or override getValue() or setValue().
 */
abstract class ContextAwareTypedData extends TypedData implements ContextAwareInterface {

  /**
   * The property name.
   *
   * @var string
   */
  protected $name;

  /**
   * The parent typed data object.
   *
   * @var \Drupal\Core\TypedData\ContextAwareInterface
   */
  protected $parent;

  /**
   * Constructs a TypedData object given its definition and context.
   *
   * @param array $definition
   *   The data definition.
   * @param string $name
   *   (optional) The name of the created property, or NULL if it is the root
   *   of a typed data tree. Defaults to NULL.
   * @param \Drupal\Core\TypedData\ContextAwareInterface $parent
   *   (optional) The parent object of the data property, or NULL if it is the
   *   root of a typed data tree. Defaults to NULL.
   *
   * @see Drupal\Core\TypedData\TypedDataManager::create()
   */
  public function __construct(array $definition, $name = NULL, ContextAwareInterface $parent = NULL) {
    $this->definition = $definition;
    $this->setContext($name, $parent);
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::setContext().
   */
  public function setContext($name = NULL, ContextAwareInterface $parent = NULL) {
    $this->parent = $parent;
    $this->name = $name;
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::getName().
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::getRoot().
   */
  public function getRoot() {
    if (isset($this->parent)) {
      return $this->parent->getRoot();
    }
    // If no parent is set, this is the root of the data tree.
    return $this;
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::getPropertyPath().
   */
  public function getPropertyPath() {
    if (isset($this->parent)) {
      // The property path of this data object is the parent's path appended
      // by this object's name.
      $prefix = $this->parent->getPropertyPath();
      return (strlen($prefix) ? $prefix . '.' : '') . $this->name;
    }
    // If no parent is set, this is the root of the data tree. Thus the property
    // path equals the name of this data object.
    elseif (isset($this->name)) {
      return $this->name;
    }
    return '';
  }

  /**
   * Implements \Drupal\Core\TypedData\ContextAwareInterface::getParent().
   *
   * @return \Drupal\Core\Entity\Field\FieldInterface
   */
  public function getParent() {
    return $this->parent;
  }
}
