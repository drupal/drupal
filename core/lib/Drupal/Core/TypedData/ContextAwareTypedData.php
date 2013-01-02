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
 * parents.
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
   * Implements ContextAwareInterface::setContext().
   */
  public function setContext($name = NULL, ContextAwareInterface $parent = NULL) {
    $this->parent = $parent;
    $this->name = $name;
  }

  /**
   * Implements ContextAwareInterface::getName().
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Implements ContextAwareInterface::getRoot().
   */
  public function getRoot() {
    if (isset($this->parent)) {
      return $this->parent->getRoot();
    }
    return $this;
  }

  /**
   * Implements ContextAwareInterface::getPropertyPath().
   */
  public function getPropertyPath() {
    if (isset($this->parent)) {
      $prefix = $this->parent->getPropertyPath();
      return (strlen($prefix) ? $prefix . '.' : '') . $this->name;
    }
    elseif (isset($this->name)) {
      return $this->name;
    }
    return '';
  }

  /**
   * Implements ContextAwareInterface::getParent().
   *
   * @return \Drupal\Core\Entity\Field\FieldInterface
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Implements TypedDataInterface::validate().
   */
  public function validate() {
    // @todo: Implement validate() method.
  }
}
