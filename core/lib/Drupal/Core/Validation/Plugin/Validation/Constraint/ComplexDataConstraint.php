<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\ComplexDataConstraint.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Complex data constraint.
 *
 * Validates properties of complex data structures.
 *
 * @Constraint(
 *   id = "ComplexData",
 *   label = @Translation("Complex data", context = "Validation")
 * )
 */
class ComplexDataConstraint extends Constraint {

  /**
   * An array of constraints for contained properties, keyed by property name.
   *
   * @var array
   */
  public $properties;

  /**
   * {@inheritdoc}
   */
  public function __construct($options = NULL) {
    // Allow skipping the 'properties' key in the options.
    if (is_array($options) && !array_key_exists('properties', $options)) {
      $options = array('properties' => $options);
    }
    parent::__construct($options);
    $constraint_manager = \Drupal::service('validation.constraint');

    // Instantiate constraint objects for array definitions.
    foreach ($this->properties as &$constraints) {
      foreach ($constraints as $id => $options) {
        if (!is_object($options)) {
          $constraints[$id] = $constraint_manager->create($id, $options);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'properties';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return array('properties');
  }
}
