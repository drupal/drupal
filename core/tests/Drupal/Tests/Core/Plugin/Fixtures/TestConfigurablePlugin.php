<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin\Fixtures;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginBase;

class TestConfigurablePlugin extends PluginBase implements ConfigurableInterface, DependentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
