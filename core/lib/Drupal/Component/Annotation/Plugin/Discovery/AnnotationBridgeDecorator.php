<?php

namespace Drupal\Component\Annotation\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;

/**
 * Ensures that all definitions are run through the annotation process.
 */
class AnnotationBridgeDecorator implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * The decorated plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * The name of the annotation that contains the plugin definition.
   *
   * @var string|null
   */
  protected $pluginDefinitionAnnotationName;

  /**
   * ObjectDefinitionDiscoveryDecorator constructor.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The discovery object that is being decorated.
   * @param string $plugin_definition_annotation_name
   *   The name of the annotation that contains the plugin definition. The class
   *   corresponding to this name must implement
   *   \Drupal\Component\Annotation\AnnotationInterface.
   */
  public function __construct(DiscoveryInterface $decorated, $plugin_definition_annotation_name) {
    $this->decorated = $decorated;
    $this->pluginDefinitionAnnotationName = $plugin_definition_annotation_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->decorated->getDefinitions();
    foreach ($definitions as $id => $definition) {
      // Annotation constructors expect an array of values. If the definition is
      // not an array, it usually means it has been processed already and can be
      // ignored.
      if (is_array($definition)) {
        $definitions[$id] = (new $this->pluginDefinitionAnnotationName($definition))->get();
      }
    }
    return $definitions;
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   *
   * @param string $method
   *   The method to call on the decorated plugin discovery.
   * @param array $args
   *   The arguments to send to the method.
   *
   * @return mixed
   *   The method result.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->decorated, $method], $args);
  }

}
