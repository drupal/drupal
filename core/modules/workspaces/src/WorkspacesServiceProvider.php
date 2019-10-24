<?php

namespace Drupal\workspaces;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines a service provider for the Workspaces module.
 */
class WorkspacesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Add the 'workspace' cache context as required.
    $renderer_config = $container->getParameter('renderer.config');
    $renderer_config['required_cache_contexts'][] = 'workspace';
    $container->setParameter('renderer.config', $renderer_config);

    // Replace the class of the 'path.alias_repository' service.
    $container->getDefinition('path.alias_repository')
      ->setClass(WorkspacesAliasRepository::class)
      ->addMethodCall('setWorkspacesManager', [new Reference('workspaces.manager')]);
  }

}
