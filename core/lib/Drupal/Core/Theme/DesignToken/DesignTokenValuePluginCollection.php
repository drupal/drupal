<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\DesignToken;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\LazyPluginCollection;
use Drupal\Core\Theme\Entity\DesignToken as DesignTokenEntity;

/**
 * A collection of design token value plugins.
 */
class DesignTokenValuePluginCollection extends LazyPluginCollection {

  public function __construct(
    protected DesignTokenValuePluginManagerInterface $manager,
    protected string $pluginId,
    protected array $configurations = [],
  ) {
    if (!empty($this->configurations)) {
      $instance_ids = array_keys($this->configurations);
      $this->instanceIds = array_combine($instance_ids, $instance_ids);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id): void {
    $this->set($instance_id, $this->manager->createInstance($this->pluginId, DesignTokenValuePluginBase::prepareConfiguration($this->configurations[$instance_id] ?? [])));
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Track each instance ID as it is updated.
    $unprocessed_instance_ids = $this->getInstanceIds();

    foreach ($configuration as $instance_id => $instance_configuration) {
      $this->configurations[$instance_id] = $instance_configuration;
      $instance = $this->get($instance_id);
      if ($instance instanceof ConfigurableInterface) {
        $instance->setConfiguration(DesignTokenValuePluginBase::prepareConfiguration($instance_configuration));
      }
      // Remove this instance ID from the list being updated.
      unset($unprocessed_instance_ids[$instance_id]);
    }

    // Remove remaining instances that had no configuration specified for them.
    foreach ($unprocessed_instance_ids as $unprocessed_instance_id) {
      $this->removeInstanceId($unprocessed_instance_id);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    $preparedConfigurations = [];
    foreach ($this->configurations as $instanceId => $configuration) {
      $instance = $this->get($instanceId);
      $preparedConfigurations[DesignTokenEntity::getConfigScopeName($instanceId)] = $instance->toDtcg();
    }
    return $preparedConfigurations;
  }

}
