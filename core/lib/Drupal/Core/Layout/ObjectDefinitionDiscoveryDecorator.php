<?php

namespace Drupal\Core\Layout;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;

/**
 * Ensures that all array-based definitions are converted to objects.
 *
 * @internal
 *   The layout system is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 *
 * @todo Move into \Drupal\Component\Plugin\Discovery in
 *   https://www.drupal.org/node/2822752.
 */
class ObjectDefinitionDiscoveryDecorator implements DiscoveryInterface {

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
   * The class corresponding to this name must implement
   * \Drupal\Component\Annotation\AnnotationInterface.
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
   *   The name of the annotation that contains the plugin definition.
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
