<?php

namespace Drupal\Component\Plugin\Context;

/**
 * A generic context interface for wrapping data a plugin needs to operate.
 */
interface ContextInterface {

  /**
   * Gets the context value.
   *
   * @return mixed
   *   The currently set context value, or NULL if it is not set.
   */
  public function getContextValue();

  /**
   * Returns whether the context has a value.
   *
   * @return bool
   *   TRUE if the context has a value, FALSE otherwise.
   */
  public function hasContextValue();

  /**
   * Gets the provided definition that the context must conform to.
   *
   * @return \Drupal\Component\Plugin\Context\ContextDefinitionInterface
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
