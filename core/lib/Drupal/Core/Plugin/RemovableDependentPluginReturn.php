<?php

declare(strict_types=1);

namespace Drupal\Core\Plugin;

/**
 * Enumeration of return values when acting on plugin dependency removal.
 *
 * @see \Drupal\Core\Plugin\RemovableDependentPluginInterface::onCollectionDependencyRemoval()
 */
enum RemovableDependentPluginReturn {

  case Changed;
  case Remove;
  case Unchanged;

}
