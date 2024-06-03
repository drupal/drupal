<?php

namespace Drupal\Core\TypedData;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
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
  use DependencySerializationTrait;
  use StringTranslationTrait;
  use TypedDataTrait;

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
  public static function createInstance($definition, $name = NULL, ?TraversableTypedDataInterface $parent = NULL) {
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
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, ?TypedDataInterface $parent = NULL) {
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
    return $this->getTypedDataManager()->getDefinition($this->definition->getDataType());
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
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraints = [];
    foreach ($this->definition->getConstraints() as $name => $options) {
      $constraints[] = $constraint_manager->create($name, $options);
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return $this->getTypedDataManager()->getValidator()->validate($this);
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
  public function setContext($name = NULL, ?TraversableTypedDataInterface $parent = NULL) {
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
      // Variables in double quotes used to leverage fast string concatenation.
      // In PHP 7+ concatenation with variable inside string is the fastest.
      // @see https://blog.blackfire.io/php-7-performance-improvements-encapsed-strings-optimization.html
      // This is being done because the code can run in the critical path.
      return $prefix !== '' ? "{$prefix}.{$this->name}" : $this->name;
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
