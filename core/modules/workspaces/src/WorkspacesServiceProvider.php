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
  public function register(ContainerBuilder $container): void {
    $modules = $container->getParameter('container.modules');

    // Add the entity query override only when the pgsql module is enabled.
    if (isset($modules['pgsql'])) {
      $container->register('pgsql.workspaces.entity.query.sql', 'Drupal\workspaces\EntityQuery\PgsqlQueryFactory')
        ->addArgument(new Reference(('database')))
        ->addArgument(new Reference(('workspaces.manager')))
        ->addArgument(new Reference(('workspaces.information')))
        ->setPublic(FALSE)
        ->setDecoratedService('pgsql.entity.query.sql', NULL, 50);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Add the 'workspace' cache context as required.
    $renderer_config = $container->getParameter('renderer.config');
    $renderer_config['required_cache_contexts'][] = 'workspace';
    $container->setParameter('renderer.config', $renderer_config);

    // Decorate the 'path_alias.manager' service.
    if ($container->hasDefinition('path_alias.manager')) {
      $container->register('workspaces.path_alias.manager', WorkspacesAliasManager::class)
        ->setPublic(FALSE)
        ->setDecoratedService('path_alias.manager', NULL, 50)
        ->addArgument(new Reference('workspaces.path_alias.manager.inner'))
        ->addArgument(new Reference('workspaces.manager'));
    }

    // Replace the class of the 'path_alias.repository' service.
    if ($container->hasDefinition('path_alias.repository')) {
      $definition = $container->getDefinition('path_alias.repository');
      if (!$definition->isDeprecated()) {
        $definition
          ->setClass(WorkspacesAliasRepository::class)
          ->addMethodCall('setWorkspacesManager', [new Reference('workspaces.manager')]);
      }
    }

    // Ensure that Layout Builder's tempstore is workspace-aware.
    if ($container->hasDefinition('layout_builder.tempstore_repository')) {
      $definition = $container->getDefinition('layout_builder.tempstore_repository');
      $definition
        ->setClass(WorkspacesLayoutTempstoreRepository::class)
        ->addMethodCall('setWorkspacesManager', [new Reference('workspaces.manager')]);
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
