<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin\Fixtures;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\ConfigurablePluginBase;

/**
 * A configurable plugin implementation used for testing.
 */
class TestConfigurablePlugin extends ConfigurablePluginBase implements DependentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
