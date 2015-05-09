<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\Context\ContextDefinitionInterface.
 */

namespace Drupal\Component\Plugin\Context;

/**
 * Interface for context definitions.
 *
 * @todo WARNING: This interface is going to receive some additions as part of
 * https://www.drupal.org/node/2346999.
 */
interface ContextDefinitionInterface {

  /**
   * Returns a human readable label.
   *
   * @return string
   *   The label.
   */
  public function getLabel();

  /**
   * Sets the human readable label.
   *
   * @param string $label
   *   The label to set.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns a human readable description.
   *
   * @return string|null
   *   The description, or NULL if no description is available.
   */
  public function getDescription();

  /**
   * Sets the human readable description.
   *
   * @param string|null $description
   *   The description to set.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the data type needed by the context.
   *
   * If the context is multiple-valued, this represents the type of each value.
   *
   * @return string
   *   The data type.
   */
  public function getDataType();

  /**
   * Sets the data type needed by the context.
   *
   * @param string $data_type
   *   The data type to set.
   *
   * @return $this
   */
  public function setDataType($data_type);

  /**
   * Returns whether the data is multi-valued, i.e. a list of data items.
   *
   * @return bool
   *   Whether the data is multi-valued; i.e. a list of data items.
   */
  public function isMultiple();

  /**
   * Sets whether the data is multi-valued.
   *
   * @param bool $multiple
   *   (optional) Whether the data is multi-valued. Defaults to TRUE.
   *
   * @return $this
   */
  public function setMultiple($multiple = TRUE);

  /**
   * Determines whether the context is required.
   *
   * For required data a non-NULL value is mandatory.
   *
   * @return bool
   *   Whether a data value is required.
   */
  public function isRequired();

  /**
   * Sets whether the data is required.
   *
   * @param bool $required
   *   (optional) Whether the data is multi-valued. Defaults to TRUE.
   *
   * @return $this
   */
  public function setRequired($required = TRUE);

  /**
   * Provides the default value for this context definition.
   *
   * @return mixed
   *   The default value or NULL if no default value is set.
   */
  public function getDefaultValue();

  /**
   * Sets the default data value.
   *
   * @param mixed $default_value
   *   The default value to be set or NULL to remove any default value.
   *
   * @return $this
   */
  public function setDefaultValue($default_value);

  /**
   * Returns an array of validation constraints.
   *
   * @return array
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   */
  public function getConstraints();

  /**
   * Sets the array of validation constraints.
   *
   * NOTE: This will override any previously set constraints. In most cases
   * ContextDefinitionInterface::addConstraint() should be used instead.
   *
   * @param array $constraints
   *   The array of constraints.
   *
   * @return $this
   *
   * @see self::addConstraint()
   */
  public function setConstraints(array $constraints);

  /**
   * Adds a validation constraint.
   *
   * @param string $constraint_name
   *   The name of the constraint to add, i.e. its plugin id.
   * @param array|null $options
   *   The constraint options as required by the constraint plugin, or NULL.
   *
   * @return $this
   */
  public function addConstraint($constraint_name, $options = NULL);

  /**
   * Returns a validation constraint.
   *
   * @param string $constraint_name
   *   The name of the constraint, i.e. its plugin id.
   *
   * @return array
   *   A validation constraint definition which can be used for instantiating a
   *   \Symfony\Component\Validator\Constraint object.
   */
  public function getConstraint($constraint_name);

}
