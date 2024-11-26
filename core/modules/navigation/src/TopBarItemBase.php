<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for top bar item plugins.
 */
abstract class TopBarItemBase extends PluginBase implements TopBarItemPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string|\Stringable {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function region(): TopBarRegion {
    return $this->pluginDefinition['region'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function build(): array;

}
