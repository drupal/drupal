<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Schema\Element.
 */

namespace Drupal\Core\Config\Schema;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * Defines a generic configuration element.
 */
abstract class Element extends TypedData {

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The configuration value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Create typed config object.
   */
  protected function parseElement($key, $data, $definition) {
    return $this->typedConfig->create($definition, $data, $key, $this);
  }

  /**
   * Build data definition object for contained elements.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected function buildDataDefinition($definition, $value, $key) {
    return $this->typedConfig->buildDataDefinition($definition, $value, $key, $this);
  }

  /**
   * Sets the typed config manager on the instance.
   *
   * This must be called immediately after construction to enable
   * self::parseElement() and self::buildDataDefinition() to work.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   */
  public function setTypedConfig(TypedConfigManagerInterface $typed_config) {
    $this->typedConfig = $typed_config;
  }

}
