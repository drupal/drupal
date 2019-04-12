<?php

namespace Drupal\Tests\Core\Plugin\Fixtures;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\ConfigurableTrait;

/**
 * A fixture to test Configurable Plugins.
 */
class TestConfigurablePlugin extends PluginBase implements ConfigurableInterface, DependentPluginInterface {

  use ConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
