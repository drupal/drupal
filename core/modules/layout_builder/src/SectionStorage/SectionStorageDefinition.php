<?php

namespace Drupal\layout_builder\SectionStorage;

use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionTrait;
use Drupal\Component\Plugin\Definition\PluginDefinition;

/**
 * Provides section storage type plugin definition.
 */
class SectionStorageDefinition extends PluginDefinition implements ContextAwarePluginDefinitionInterface {

  use ContextAwarePluginDefinitionTrait;

  /**
   * The plugin weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * Any additional properties and values.
   *
   * @var array
   */
  protected $additional = [];

  /**
   * LayoutDefinition constructor.
   *
   * @param array $definition
   *   An array of values from the annotation.
   */
  public function __construct(array $definition = []) {
    // If there are context definitions in the plugin definition, they should
    // be added to this object using ::addContextDefinition() so that they can
    // be manipulated using other ContextAwarePluginDefinitionInterface methods.
    if (isset($definition['context_definitions'])) {
      foreach ($definition['context_definitions'] as $name => $context_definition) {
        $this->addContextDefinition($name, $context_definition);
      }
      unset($definition['context_definitions']);
    }

    foreach ($definition as $property => $value) {
      $this->set($property, $value);
    }
  }

  /**
   * Gets any arbitrary property.
   *
   * @param string $property
   *   The property to retrieve.
   *
   * @return mixed
   *   The value for that property, or NULL if the property does not exist.
   */
  public function get($property) {
    if (property_exists($this, $property)) {
      $value = $this->{$property} ?? NULL;
    }
    else {
      $value = $this->additional[$property] ?? NULL;
    }
    return $value;
  }

  /**
   * Sets a value to an arbitrary property.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function set($property, $value) {
    if (property_exists($this, $property)) {
      $this->{$property} = $value;
    }
    else {
      $this->additional[$property] = $value;
    }
    return $this;
  }

  /**
   * Returns the plugin weight.
   *
   * @return int
   *   The plugin weight.
   */
  public function getWeight() {
    return $this->weight;
  }

}
