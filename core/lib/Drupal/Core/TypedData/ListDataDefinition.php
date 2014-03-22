<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\ListDataDefinition.
 */

namespace Drupal\Core\TypedData;

/**
 * A typed data definition class for defining lists.
 */
class ListDataDefinition extends DataDefinition implements ListDataDefinitionInterface {

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
   * @return \Drupal\Core\TypedData\ListDataDefinition
   *   A new List Data Definition object.
   */
  public static function create($item_type) {
    return static::createFromItemType($item_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($type) {
    $definition = parent::createFromDataType($type);
    // If nothing else given, default to a list of 'any' items.
    $definition->itemDefinition = DataDefinition::create('any');
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromItemType($item_type) {
    return new static(array(), \Drupal::typedDataManager()->createDataDefinition($item_type));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values = array(), DataDefinitionInterface $item_definition = NULL) {
    $this->definition = $values;
    $this->itemDefinition = $item_definition;
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
      $item_type_definition = \Drupal::typedDataManager()
        ->getDefinition($this->getItemDefinition()->getDataType());
      if (!$item_type_definition) {
        throw new \LogicException(format_string('An invalid data type @plugin_id has been specified for list items.', array('@plugin_id' => $this->getItemDefinition()->getDataType())));
      }
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
