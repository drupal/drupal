<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TypedData.
 */

namespace Drupal\Core\TypedData;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * The abstract base class for typed data.
 *
 * Classes deriving from this base class have to declare $value
 * or override getValue() or setValue().
 */
abstract class TypedData implements TypedDataInterface, PluginInspectionInterface {

  /**
   * The data definition.
   *
   * @var array
   */
  protected $definition;

  /**
   * The property name.
   *
   * @var string
   */
  protected $name;

  /**
   * The parent typed data object.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
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
   * @param \Drupal\Core\TypedData\TypedDataInterface $parent
   *   (optional) The parent object of the data property, or NULL if it is the
   *   root of a typed data tree. Defaults to NULL.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   */
  public function __construct(array $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    $this->definition = $definition;
    $this->parent = $parent;
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->definition['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return \Drupal::typedData()->getDefinition($this->definition['type']);
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getDefinition().
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getValue().
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::setValue().
   */
  public function setValue($value, $notify = TRUE) {
    $this->value = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getString().
   */
  public function getString() {
    return (string) $this->getValue();
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getConstraints().
   */
  public function getConstraints() {
    // @todo: Add the typed data manager as proper dependency.
    return \Drupal::typedData()->getConstraints($this->definition);
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::validate().
   */
  public function validate() {
    // @todo: Add the typed data manager as proper dependency.
    return \Drupal::typedData()->getValidator()->validate($this);
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to no default value.
    $this->setValue(NULL, $notify);
    return $this;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::setContext().
   */
  public function setContext($name = NULL, TypedDataInterface $parent = NULL) {
    $this->parent = $parent;
    $this->name = $name;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getName().
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getRoot().
   */
  public function getRoot() {
    if (isset($this->parent)) {
      return $this->parent->getRoot();
    }
    // If no parent is set, this is the root of the data tree.
    return $this;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getPropertyPath().
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
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getParent().
   *
   * @return \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public function getParent() {
    return $this->parent;
  }
}
