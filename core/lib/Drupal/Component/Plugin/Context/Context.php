<?php

namespace Drupal\Component\Plugin\Context;

use Drupal\Component\Plugin\Exception\ContextException;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;

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
   * @var \Drupal\Component\Plugin\Context\ContextDefinitionInterface
   */
  protected $contextDefinition;

  /**
   * Create a context object.
   *
   * @param \Drupal\Component\Plugin\Context\ContextDefinitionInterface $context_definition
   *   The context definition.
   * @param mixed|null $context_value
   *   The value of the context.
   */
  public function __construct(ContextDefinitionInterface $context_definition, $context_value = NULL) {
    $this->contextDefinition = $context_definition;
    $this->contextValue = $context_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextValue() {
    // Support optional contexts.
    if (!isset($this->contextValue)) {
      $definition = $this->getContextDefinition();
      $default_value = $definition->getDefaultValue();

      if (!isset($default_value) && $definition->isRequired()) {
        $type = $definition->getDataType();
        throw new ContextException(sprintf("The %s context is required and not present.", $type));
      }
      // Keep the default value here so that subsequent calls don't have to look
      // it up again.
      $this->contextValue = $default_value;
    }
    return $this->contextValue;
  }

  /**
   * {@inheritdoc}
   */
  public function hasContextValue() {
    return (bool) $this->contextValue || (bool) $this->getContextDefinition()->getDefaultValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition() {
    return $this->contextDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    if (empty($this->contextDefinition['class'])) {
      throw new ContextException("An error was encountered while trying to validate the context.");
    }
    return [new Type($this->contextDefinition['class'])];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $validator = Validation::createValidatorBuilder()
      ->getValidator();
    return $validator->validateValue($this->getContextValue(), $this->getConstraints());
  }

}
