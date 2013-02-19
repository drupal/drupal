<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\Context\ContextInterface.
 */

namespace Drupal\Component\Plugin\Context;

/**
 * A generic context interface for wrapping data a plugin needs to operate.
 */
interface ContextInterface {

  /**
   * Sets the context value.
   *
   * @param mixed $value
   *   The value of this context, matching the context definition.
   *
   * @see \Drupal\Component\Plugin\Context\ContextInterface::setContextDefinition().
   */
  public function setContextValue($value);

  /**
   * Gets the context value.
   *
   * @return mixed
   *   The currently set context value, or NULL if it is not set.
   */
  public function getContextValue();

  /**
   * Sets the definition that the context must conform to.
   *
   * @param array $contextDefinition
   *   A defining characteristic representation of the context against which
   *   that context can be validated. This is typically an array having a
   *   class name set under the 'class' key, but it could be extended to support
   *   other notations.
   */
  public function setContextDefinition(array $contextDefinition);

  /**
   * Gets the provided definition that the context must conform to.
   *
   * @return array
   *   The defining characteristic representation of the context.
   */
  public function getContextDefinition();

  /**
   * Gets a list of validation constraints.
   *
   * @return array
   *   Array of constraints, each being an instance of
   *   \Symfony\Component\Validator\Constraint.
   */
  public function getConstraints();

  /**
   * Validates the set context value.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validate();

}
