<?php

namespace Drupal\rest\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\rest\RestResourceConfigInterface;

/**
 * Defines a RestResourceConfig configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "rest_resource_config",
 *   label = @Translation("REST resource configuration"),
 *   label_collection = @Translation("REST resource configurations"),
 *   label_singular = @Translation("REST resource configuration"),
 *   label_plural = @Translation("REST resource configurations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count REST resource configuration",
 *     plural = "@count REST resource configurations",
 *   ),
 *   config_prefix = "resource",
 *   admin_permission = "administer rest resources",
 *   label_callback = "getLabelFromPlugin",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   config_export = {
 *     "id",
 *     "plugin_id",
 *     "granularity",
 *     "configuration"
 *   }
 * )
 */
class RestResourceConfig extends ConfigEntityBase implements RestResourceConfigInterface {

  /**
   * The REST resource config id.
   *
   * @var string
   */
  protected $id;

  /**
   * The REST resource plugin id.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * The REST resource configuration granularity.
   *
   * Currently either:
   * - \Drupal\rest\RestResourceConfigInterface::METHOD_GRANULARITY
   * - \Drupal\rest\RestResourceConfigInterface::RESOURCE_GRANULARITY
   *
   * @var string
   */
  protected $granularity;

  /**
   * The REST resource configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The rest resource plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    // The config entity id looks like the plugin id but uses __ instead of :
    // because : is not valid for config entities.
    if (!isset($this->plugin_id) && isset($this->id)) {
      // Generate plugin_id on first entity creation.
      $this->plugin_id = str_replace('.', ':', $this->id);
    }
  }

  /**
   * The label callback for this configuration entity.
   *
   * @return string The label.
   */
  protected function getLabelFromPlugin() {
    $plugin_definition = $this->getResourcePluginManager()
      ->getDefinition(['id' => $this->plugin_id]);
    return $plugin_definition['label'];
  }

  /**
   * Returns the resource plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected function getResourcePluginManager() {
    if (!isset($this->pluginManager)) {
      $this->pluginManager = \Drupal::service('plugin.manager.rest');
    }
    return $this->pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourcePlugin() {
    return $this->getPluginCollections()['resource']->get($this->plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getMethods() {
    switch ($this->granularity) {
      case RestResourceConfigInterface::METHOD_GRANULARITY:
        return $this->getMethodsForMethodGranularity();
      case RestResourceConfigInterface::RESOURCE_GRANULARITY:
        return $this->configuration['methods'];
      default:
        throw new \InvalidArgumentException('Invalid granularity specified.');
    }
  }

  /**
   * Retrieves a list of supported HTTP methods for this resource.
   *
   * @return string[]
   *   A list of supported HTTP methods.
   */
  protected function getMethodsForMethodGranularity() {
    $methods = array_keys($this->configuration);
    return array_map([$this, 'normalizeRestMethod'], $methods);
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationProviders($method) {
    switch ($this->granularity) {
      case RestResourceConfigInterface::METHOD_GRANULARITY:
        return $this->getAuthenticationProvidersForMethodGranularity($method);
      case RestResourceConfigInterface::RESOURCE_GRANULARITY:
        return $this->configuration['authentication'];
      default:
        throw new \InvalidArgumentException('Invalid granularity specified.');
    }
  }

  /**
   * Retrieves a list of supported authentication providers.
   *
   * @param string $method
   *   The request method e.g GET or POST.
   *
   * @return string[]
   *   A list of supported authentication provider IDs.
   */
  public function getAuthenticationProvidersForMethodGranularity($method) {
    $method = $this->normalizeRestMethod($method);
    if (in_array($method, $this->getMethods()) && isset($this->configuration[$method]['supported_auth'])) {
      return $this->configuration[$method]['supported_auth'];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormats($method) {
    switch ($this->granularity) {
      case RestResourceConfigInterface::METHOD_GRANULARITY:
        return $this->getFormatsForMethodGranularity($method);
      case RestResourceConfigInterface::RESOURCE_GRANULARITY:
        return $this->configuration['formats'];
      default:
        throw new \InvalidArgumentException('Invalid granularity specified.');
    }
  }

  /**
   * Retrieves a list of supported response formats.
   *
   * @param string $method
   *   The request method e.g GET or POST.
   *
   * @return string[]
   *   A list of supported format IDs.
   */
  protected function getFormatsForMethodGranularity($method) {
    $method = $this->normalizeRestMethod($method);
    if (in_array($method, $this->getMethods()) && isset($this->configuration[$method]['supported_formats'])) {
      return $this->configuration[$method]['supported_formats'];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'resource' => new DefaultSingleLazyPluginCollection($this->getResourcePluginManager(), $this->plugin_id, []),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    foreach ($this->getRestResourceDependencies()->calculateDependencies($this) as $type => $dependencies) {
      foreach ($dependencies as $dependency) {
        $this->addDependency($type, $dependency);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $parent = parent::onDependencyRemoval($dependencies);

    // If the dependency problems are not marked as fixed at this point they
    // should be related to the resource plugin and the config entity should
    // be deleted.
    $changed = $this->getRestResourceDependencies()->onDependencyRemoval($this, $dependencies);
    return $parent || $changed;
  }

  /**
   * Returns the REST resource dependencies.
   *
   * @return \Drupal\rest\Entity\ConfigDependencies
   */
  protected function getRestResourceDependencies() {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(ConfigDependencies::class);
  }

  /**
   * Normalizes the method.
   *
   * @param string $method
   *   The request method.
   *
   * @return string
   *   The normalized request method.
   */
  protected function normalizeRestMethod($method) {
    return strtoupper($method);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    \Drupal::service('router.builder')->setRebuildNeeded();
  }

}
