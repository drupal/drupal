<?php

declare(strict_types=1);

namespace Drupal\container_rebuild_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * The service provider for testing container rebuilds.
 */
class ContainerRebuildTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $count = $container->get('state')->get('container_rebuild_test.count', 0);
    $container->get('state')->set('container_rebuild_test.count', ++$count);
  }

}
