<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\Context\ContextInterface.
 */

namespace Drupal\Component\Plugin\Context;

use Drupal\Component\Plugin\Exception\ContextException;

/**
 * A generic context interface for wrapping data a plugin needs to operate.
 */
interface ContextInterface {

  /**
   * Sets the context value.
   *
   * @param mixed $value
   *   The value of this context, generally an object based upon the class
   *   matching the definition passed to setContextDefinition().
   */
  public function setContextValue($value);

  /**
   * Gets the context value.
   *
   * @return mixed
   *   The currently set context value within this class.
   */
  public function getContextValue();

  /**
   * Sets the definition that the context must conform to.
   *
   * @param mixed $contextDefinition
   *   A defining characteristic representation of the context against which
   *   that context can be validated. This is typically a class name, but could
   *   be extended to support other validation notation.
   */
  public function setContextDefinition(array $contextDefinition);

  /**
   * Gets the provided definition that the context must conform to.
   *
   * @return mixed
   *   The defining characteristic representation of the context.
   */
  public function getContextDefinition();

  /**
   * Validate the provided context value against the provided definition.
   *
   * @param mixed $value
   *   The context value that should be validated against the context
   *   definition.
   *
   * @return mixed
   *   Returns the context value passed to it. If it fails validation, an
   *   exception will be thrown.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If validation fails.
   */
  public function validate($value);

}
