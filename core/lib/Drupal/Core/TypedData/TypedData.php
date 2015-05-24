<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TypedData.
 */

namespace Drupal\Core\TypedData;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The abstract base class for typed data.
 *
 * Classes deriving from this base class have to declare $value
 * or override getValue() or setValue().
 *
 * @ingroup typed_data
 */
abstract class TypedData implements TypedDataInterface, PluginInspectionInterface {

  use StringTranslationTrait;

  /**
   * The data definition.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
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
   * @var \Drupal\Core\TypedData\TraversableTypedDataInterface|null
   */
  protected $parent;

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    return new static($definition, $name, $parent);
  }

  /**
   * Constructs a TypedData object given its definition and context.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   * @param string $name
   *   (optional) The name of the created property, or NULL if it is the root
   *   of a typed data tree. Defaults to NULL.
   * @param \Drupal\Core\TypedData\TypedDataInterface $parent
   *   (optional) The parent object of the data property, or NULL if it is the
   *   root of a typed data tree. Defaults to NULL.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   *
   * @todo When \Drupal\Core\Config\TypedConfigManager has been fixed to use
   *   class-based definitions, type-hint $definition to
   *   DataDefinitionInterface. https://www.drupal.org/node/1928868
   */
  public function __construct($definition, $name = NULL, TypedDataInterface $parent = NULL) {
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
    return \Drupal::typedDataManager()->getDefinition($this->definition->getDataType());
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDefinition() {
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->value = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    return (string) $this->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    // @todo: Add the typed data manager as proper dependency.
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = array();
    foreach ($this->definition->getConstraints() as $name => $options) {
      $constraints[] = $constraint_manager->create($name, $options);
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // @todo: Add the typed data manager as proper dependency.
    return \Drupal::typedDataManager()->getValidator()->validate($this);
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
   * {@inheritdoc}
   */
  public function setContext($name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $this->parent = $parent;
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoot() {
    if (isset($this->parent)) {
      return $this->parent->getRoot();
    }
    // If no parent is set, this is the root of the data tree.
    return $this;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->parent;
  }
}
