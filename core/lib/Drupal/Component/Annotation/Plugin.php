<?php

namespace Drupal\Component\Annotation;

use Drupal\Component\Utility\NestedArray;

/**
 * Defines a Plugin annotation object.
 *
 * Annotations in plugin classes can use this class in order to pass various
 * metadata about the plugin through the parser to
 * DiscoveryInterface::getDefinitions() calls. This allows the metadata
 * of a class to be located with the class itself, rather than in module-based
 * info hooks.
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class Plugin implements AnnotationInterface {

  /**
   * The plugin definition read from the class annotation.
   *
   * @var array
   */
  protected $definition;

  /**
   * Constructs a Plugin object.
   *
   * Builds up the plugin definition and invokes the get() method for any
   * classed annotations that were used.
   */
  public function __construct($values) {
    $reflection = new \ReflectionClass($this);
    // Only keep actual default values by ignoring NULL values.
    $defaults = array_filter($reflection->getDefaultProperties(), function ($value) {
      return $value !== NULL;
    });
    $parsed_values = $this->parse($values);
    $this->definition = NestedArray::mergeDeepArray([$defaults, $parsed_values], TRUE);
  }

  /**
   * Parses an annotation into its definition.
   *
   * @param array $values
   *   The annotation array.
   *
   * @return array
   *   The parsed annotation as a definition.
   */
  protected function parse(array $values) {
    $definitions = [];
    foreach ($values as $key => $value) {
      if ($value instanceof AnnotationInterface) {
        $definitions[$key] = $value->get();
      }
      elseif (is_array($value)) {
        $definitions[$key] = $this->parse($value);
      }
      else {
        $definitions[$key] = $value;
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return isset($this->definition['provider']) ? $this->definition['provider'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider($provider) {
    $this->definition['provider'] = $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->definition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->definition['class'];
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    $this->definition['class'] = $class;
  }

}
