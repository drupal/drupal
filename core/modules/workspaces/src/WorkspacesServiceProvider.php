<?php

namespace Drupal\workspaces;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Update\UpdateKernel;
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

    // Replace the class of the 'path_alias.repository' service.
    if ($container->hasDefinition('path_alias.repository')) {
      $definition = $container->getDefinition('path_alias.repository');
      if (!$definition->isDeprecated()) {
        $definition
          ->setClass(WorkspacesAliasRepository::class)
          ->addMethodCall('setWorkspacesManager', [new Reference('workspaces.manager')]);
      }
    }

    // Ensure that there's no active workspace while running database updates by
    // removing the relevant tag from all workspace negotiator services.
    if ($container->get('kernel') instanceof UpdateKernel) {
      foreach ($container->getDefinitions() as $definition) {
        if ($definition->hasTag('workspace_negotiator')) {
          $definition->clearTag('workspace_negotiator');
        }
      }
    }
  }

}
