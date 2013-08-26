<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Type\FieldType\ConfigField.
 */

namespace Drupal\field\Plugin\Type\FieldType;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Entity\Field\Field;
use Drupal\field\Field as FieldAPI;

/**
 * Represents a configurable entity field.
 */
class ConfigField extends Field implements ConfigFieldInterface {

  /**
   * The Field instance definition.
   *
   * @var \Drupal\field\Entity\FieldInstance
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    if (isset($definition['instance'])) {
      $this->instance = $definition['instance'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance() {
    if (!isset($this->instance) && $parent = $this->getParent()) {
      $instances = FieldAPI::fieldInfo()->getBundleInstances($parent->entityType(), $parent->bundle());
      $this->instance = $instances[$this->getName()];
    }
    return $this->instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition() {
    return $this->getInstance();
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = array();
    // Check that the number of values doesn't exceed the field cardinality. For
    // form submitted values, this can only happen with 'multiple value'
    // widgets.
    $cardinality = $this->getFieldDefinition()->getFieldCardinality();
    if ($cardinality != FIELD_CARDINALITY_UNLIMITED) {
      $constraints[] = \Drupal::typedData()
        ->getValidationConstraintManager()
        ->create('Count', array(
          'max' => $cardinality,
          'maxMessage' => t('%name: this field cannot hold more than @count values.', array('%name' => $this->getFieldDefinition()->getFieldLabel(), '@count' => $cardinality)),
        ));
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultValue() {
    return $this->getInstance()->getFieldDefaultValue($this->getParent());
  }

}
