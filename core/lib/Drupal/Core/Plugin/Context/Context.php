<?php

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\Context as ComponentContext;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataTrait;

/**
 * A Drupal specific context wrapper class.
 */
class Context extends ComponentContext implements ContextInterface {

  use TypedDataTrait;

  /**
   * The data associated with the context.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $contextData;

  /**
   * The definition to which a context must conform.
   *
   * @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface
   */
  protected $contextDefinition;

  /**
   * The cacheability metadata.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheabilityMetadata;

  /**
   * Create a context object.
   *
   * @param \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context_definition
   *   The context definition.
   * @param mixed $context_value|null
   *   The context value object.
   */
  public function __construct(ContextDefinitionInterface $context_definition, $context_value = NULL) {
    parent::__construct($context_definition, NULL);
    $this->cacheabilityMetadata = new CacheableMetadata();
    if (!is_null($context_value)) {
      $this->setContextValue($context_value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContextValue() {
    if (!isset($this->contextData)) {
      $definition = $this->getContextDefinition();
      $default_value = $definition->getDefaultValue();

      if (isset($default_value)) {
        // Keep the default value here so that subsequent calls don't have to
        // look it up again.
        $this->setContextValue($default_value);
      }
      elseif ($definition->isRequired()) {
        $type = $definition->getDataType();
        throw new ContextException("The '$type' context is required and not present.");
      }
      return $default_value;
    }
    return $this->getTypedDataManager()->getCanonicalRepresentation($this->contextData);
  }

  /**
   * {@inheritdoc}
   */
  public function hasContextValue() {
    return (bool) $this->contextData || parent::hasContextValue();
  }

  /**
   * Sets the context value.
   *
   * @param mixed $value
   *   The value of this context, matching the context definition.
   */
  protected function setContextValue($value) {
    // Add the value as a cacheable dependency only if implements the interface
    // to prevent it from disabling caching with a max-age 0.
    if ($value instanceof CacheableDependencyInterface) {
      $this->addCacheableDependency($value);
    }
    if ($value instanceof TypedDataInterface) {
      $this->contextData = $value;
    }
    else {
      $this->contextData = $this->getTypedDataManager()->create($this->contextDefinition->getDataDefinition(), $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return $this->contextDefinition->getConstraints();
  }

  /**
   * {@inheritdoc}
   */
  public function getContextData() {
    if (!isset($this->contextData)) {
      $definition = $this->getContextDefinition();
      $default_value = $definition->getDefaultValue();
      // Store the default value so that subsequent calls don't have to look
      // it up again.
      $this->contextData = $this->getTypedDataManager()->create($definition->getDataDefinition(), $default_value);
    }
    return $this->contextData;
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
  public function validate() {
    return $this->getContextData()->validate();
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheableDependency($dependency) {
    $this->cacheabilityMetadata = $this->cacheabilityMetadata->merge(CacheableMetadata::createFromObject($dependency));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->cacheabilityMetadata->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->cacheabilityMetadata->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->cacheabilityMetadata->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromContext(ContextInterface $old_context, $value) {
    $context = new static($old_context->getContextDefinition(), $value);
    $context->addCacheableDependency($old_context);
    if (method_exists($old_context, 'getTypedDataManager')) {
      $context->setTypedDataManager($old_context->getTypedDataManager());
    }
    return $context;
  }

}
