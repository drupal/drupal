<?php

namespace Drupal\layout_builder;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Provides a value object for a section component.
 *
 * A component represents the smallest part of a layout (for example, a block).
 * Components wrap a renderable plugin, currently using
 * \Drupal\Core\Block\BlockPluginInterface, and contain the layout region
 * within the section layout where the component will be rendered.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 *
 * @see \Drupal\Core\Layout\LayoutDefinition
 * @see \Drupal\layout_builder\Section
 * @see \Drupal\layout_builder\SectionStorageInterface
 *
 * @todo Determine whether to retain the name 'component' in
 *   https://www.drupal.org/project/drupal/issues/2929783.
 * @todo Determine whether an interface will be provided for this in
 *   https://www.drupal.org/project/drupal/issues/2930334.
 */
class SectionComponent {

  /**
   * The UUID of the component.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The region the component is placed in.
   *
   * @var string
   */
  protected $region;

  /**
   * An array of plugin configuration.
   *
   * @var mixed[]
   */
  protected $configuration;

  /**
   * The weight of the component.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * Any additional properties and values.
   *
   * @var mixed[]
   */
  protected $additional = [];

  /**
   * Constructs a new SectionComponent.
   *
   * @param string $uuid
   *   The UUID.
   * @param string $region
   *   The region.
   * @param mixed[] $configuration
   *   The plugin configuration.
   * @param mixed[] $additional
   *   An additional values.
   */
  public function __construct($uuid, $region, array $configuration = [], array $additional = []) {
    $this->uuid = $uuid;
    $this->region = $region;
    $this->configuration = $configuration;
    $this->additional = $additional;
  }

  /**
   * Returns the renderable array for this component.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of available contexts.
   *
   * @return array
   *   A renderable array representing the content of the component.
   */
  public function toRenderArray(array $contexts = []) {
    $output = [];

    $plugin = $this->getPlugin($contexts);
    // @todo Figure out the best way to unify fields and blocks and components
    //   in https://www.drupal.org/node/1875974.
    if ($plugin instanceof BlockPluginInterface) {
      $access = $plugin->access($this->currentUser(), TRUE);
      $cacheability = CacheableMetadata::createFromObject($access);

      if ($access->isAllowed()) {
        $cacheability->addCacheableDependency($plugin);
        // @todo Move this to BlockBase in https://www.drupal.org/node/2931040.
        $output = [
          '#theme' => 'block',
          '#configuration' => $plugin->getConfiguration(),
          '#plugin_id' => $plugin->getPluginId(),
          '#base_plugin_id' => $plugin->getBaseId(),
          '#derivative_plugin_id' => $plugin->getDerivativeId(),
          '#weight' => $this->getWeight(),
          'content' => $plugin->build(),
        ];
      }
      $cacheability->applyTo($output);
    }
    return $output;
  }

  /**
   * Gets any arbitrary property for the component.
   *
   * @param string $property
   *   The property to retrieve.
   *
   * @return mixed
   *   The value for that property, or NULL if the property does not exist.
   */
  public function get($property) {
    if (property_exists($this, $property)) {
      $value = isset($this->{$property}) ? $this->{$property} : NULL;
    }
    else {
      $value = isset($this->additional[$property]) ? $this->additional[$property] : NULL;
    }
    return $value;
  }

  /**
   * Sets a value to an arbitrary property for the component.
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
   * Gets the region for the component.
   *
   * @return string
   *   The region.
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * Sets the region for the component.
   *
   * @param string $region
   *   The region.
   *
   * @return $this
   */
  public function setRegion($region) {
    $this->region = $region;
    return $this;
  }

  /**
   * Gets the weight of the component.
   *
   * @return int
   *   The zero-based weight of the component.
   *
   * @throws \UnexpectedValueException
   *   Thrown if the weight was never set.
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Sets the weight of the component.
   *
   * @param int $weight
   *   The zero-based weight of the component.
   *
   * @return $this
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * Gets the component plugin configuration.
   *
   * @return mixed[]
   *   The component plugin configuration.
   */
  protected function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Sets the plugin configuration.
   *
   * @param mixed[] $configuration
   *   The plugin configuration.
   *
   * @return $this
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    return $this;
  }

  /**
   * Gets the plugin ID.
   *
   * @return string
   *   The plugin ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown if the plugin ID cannot be found.
   */
  protected function getPluginId() {
    if (empty($this->configuration['id'])) {
      throw new PluginException(sprintf('No plugin ID specified for component with "%s" UUID', $this->uuid));
    }
    return $this->configuration['id'];
  }

  /**
   * Gets the UUID for this component.
   *
   * @return string
   *   The UUID.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Gets the plugin for this component.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts to set on the plugin.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface
   *   The plugin.
   */
  public function getPlugin(array $contexts = []) {
    $plugin = $this->pluginManager()->createInstance($this->getPluginId(), $this->getConfiguration());
    if ($contexts && $plugin instanceof ContextAwarePluginInterface) {
      $this->contextHandler()->applyContextMapping($plugin, $contexts);
    }
    return $plugin;
  }

  /**
   * Wraps the component plugin manager.
   *
   * @return \Drupal\Core\Block\BlockManagerInterface
   *   The plugin manager.
   */
  protected function pluginManager() {
    return \Drupal::service('plugin.manager.block');
  }

  /**
   * Wraps the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   *   The context handler.
   */
  protected function contextHandler() {
    return \Drupal::service('context.handler');
  }

  /**
   * Wraps the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser() {
    return \Drupal::currentUser();
  }

}
