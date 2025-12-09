<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin\Fixtures;

use Drupal\Core\Plugin\ConfigurablePluginBase;
use Drupal\Core\Plugin\RemovableDependentPluginInterface;
use Drupal\Core\Plugin\RemovableDependentPluginReturn;

/**
 * A configurable plugin implementation used for testing.
 */
class TestConfigurablePlugin extends ConfigurablePluginBase implements RemovableDependentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function onCollectionDependencyRemoval(array $dependencies): RemovableDependentPluginReturn {
    return RemovableDependentPluginReturn::Unchanged;
  }

}
