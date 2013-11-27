<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\ListDefinition.
 */

namespace Drupal\Core\TypedData;

/**
 * A class for defining data based on defined data types.
 */
class ListDefinition extends DataDefinition implements ListDefinitionInterface {

  /**
   * The data definition of a list item.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected $itemDefinition;

  /**
   * Creates a new list definition.
   *
   * @param string $item_type
   *   The data type of the list items; e.g., 'string', 'integer' or 'any'.
   *
   * @return \Drupal\Core\TypedData\ListDefinition
   *   A new List Data Definition object.
   */
  public static function create($item_type) {
    return new static(array(), DataDefinition::create($item_type));
  }

  /**
   * {@inheritdoc}
   *
   * @param
   */
  public function __construct(array $definition = array(), DataDefinitionInterface $item_definition = NULL) {
    parent::__construct($definition);
    $this->itemDefinition = isset($item_definition) ? $item_definition : DataDefinition::create('any');
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    return 'list';
  }

  /**
   * {@inheritdoc}
   */
  public function setDataType($type) {
    if ($type != 'list') {
      throw new \LogicException('Lists must always be of data type "list".');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    $class = isset($this->definition['class']) ? $this->definition['class'] : NULL;
    if (!empty($class)) {
      return $class;
    }
    else {
      // If a list definition is used but no class has been specified, derive
      // the default list class from the item type.
      $item_type_definition = \Drupal::typedData()
        ->getDefinition($this->getItemDefinition()->getDataType());
      return $item_type_definition['list_class'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefinition() {
    return $this->itemDefinition;
  }

  /**
   * Sets the item definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinition $definition
   *   A list item's data definition.
   *
   * @return self
   *   The object itself for chaining.
   */
  public function setItemDefinition(DataDefinitionInterface $definition) {
    $this->itemDefinition = $definition;
    return $this;
  }
}
