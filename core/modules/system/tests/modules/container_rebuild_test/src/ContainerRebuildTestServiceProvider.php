<?php

declare(strict_types=1);

namespace Drupal\container_rebuild_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The service provider for testing container rebuilds.
 */
class ContainerRebuildTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $state = $container->get('state');
    $count = $state->get('container_rebuild_test.count', 0);
    $state->set('container_rebuild.trusted_hosts', Request::getTrustedHosts());
    $state->set('container_rebuild_test.count', ++$count);
  }

}
