<?php

namespace Drupal\Core\TypedData;

/**
 * A typed data definition class for defining references.
 *
 * Note that this definition class assumes that the data type for referencing
 * a certain target type is named "{TARGET_TYPE}_reference".
 *
 * @see \Drupal\Core\TypedData\DataReferenceBase
 */
class DataReferenceDefinition extends DataDefinition implements DataReferenceDefinitionInterface {

  /**
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected $targetDefinition;

  /**
   * Creates a new data reference definition.
   *
   * @param string $target_data_type
   *   The data type of the referenced data.
   *
   * @return static
   */
  public static function create($target_data_type) {
    // This assumes implementations use a "TYPE_reference" naming pattern.
    $definition = parent::create($target_data_type . '_reference');
    return $definition->setTargetDefinition(\Drupal::typedDataManager()->createDataDefinition($target_data_type));
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($data_type) {
    if (substr($data_type, -strlen('_reference')) != '_reference') {
      throw new \InvalidArgumentException('Data type must be of the form "{TARGET_TYPE}_reference"');
    }
    // Cut of the _reference suffix.
    return static::create(substr($data_type, 0, strlen($data_type) - strlen('_reference')));
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetDefinition() {
    return $this->targetDefinition;
  }

  /**
   * Sets the definition of the referenced data.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The target definition to set.
   *
   * @return $this
   */
  public function setTargetDefinition(DataDefinitionInterface $definition) {
    $this->targetDefinition = $definition;
    return $this;
  }

}
