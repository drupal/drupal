<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\Context\Context.
 */

namespace Drupal\Component\Plugin\Context;

use Drupal\Component\Plugin\Exception\ContextException;

/**
 * A generic context class for wrapping data a plugin needs to operate.
 */
class Context implements ContextInterface {

  /**
   * The value of the context.
   *
   * @var mixed
   */
  protected $contextValue;

  /**
   * The definition to which a context must conform.
   *
   * @var array
   */
  protected $contextDefinition;

  /**
   * Sets the contextDefinition for us without needing to call the setter.
   */
  public function __construct(array $context_definition) {
    $this->contextDefinition = $context_definition;
  }

  /**
   * Implements \Drupal\Component\Plugin\Context\ContextInterface::setContextValue().
   */
  public function setContextValue($value) {
    $value = $this->validate($value);
    $this->contextValue = $value;
  }

  /**
   * Implements \Drupal\Component\Plugin\Context\ContextInterface::getContextValue().
   */
  public function getContextValue() {
    return $this->contextValue;
  }

  /**
   * Implements \Drupal\Component\Plugin\Context\ContextInterface::setContextDefinition().
   */
  public function setContextDefinition(array $context_definition) {
    $this->contextDefinition = $context_definition;
  }

  /**
   * Implements \Drupal\Component\Plugin\Context\ContextInterface::getContextDefinition().
   */
  public function getContextDefinition() {
    return $this->contextDefinition;
  }

  /**
   * Implements \Drupal\Component\Plugin\Context\ContextInterface::validate().
   *
   * The default validation method only supports instance of checks between the
   * contextDefintion and the contextValue. Other formats of context
   * definitions can be supported through a subclass.
   */
  public function validate($value) {
    // Check to make sure we have a class name, and that the passed context is
    // an instance of that class name.
    if (!empty($this->contextDefinition['class'])) {
      if ($value instanceof $this->contextDefinition['class']) {
        return $value;
      }
      throw new ContextException("The context passed was not an instance of {$this->contextDefinition['class']}.");
    }
    throw new ContextException("An error was encountered while trying to validate the context.");
  }

}
