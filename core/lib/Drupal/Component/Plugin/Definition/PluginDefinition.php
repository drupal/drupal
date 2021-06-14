<?php

namespace Drupal\Component\Plugin\Definition;

/**
 * Provides object-based plugin definitions.
 */
class PluginDefinition implements PluginDefinitionInterface {

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $id;

  /**
   * A fully qualified class name.
   *
   * @var string
   */
  protected $class;

  /**
   * The plugin provider.
   *
   * @var string
   */
  protected $provider;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    $this->class = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->provider;
  }

}
