<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Defines a compiler pass to allow automatic override per backend.
 *
 * A module developer has to tag a backend service with "backend_overridable":
 * @code
 * custom_service:
 *   class: ...
 *   tags:
 *     - { name: backend_overridable }
 * @endcode
 *
 * As a site admin you set the 'default_backend' in your services.yml file:
 * @code
 * parameters:
 *   default_backend: sqlite
 * @endcode
 *
 * As a developer for alternative storage engines you register a service with
 * $yourbackend.$original_service:
 *
 * @code
 * sqlite.custom_service:
 *   class: ...
 * @endcode
 */
class BackendCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $driver_backend = NULL;
    if ($container->hasParameter('default_backend')) {
      $default_backend = $container->getParameter('default_backend');
      // Opt out from the default backend.
      if (!$default_backend) {
        return;
      }
    }
    else {
      try {
        $driver_backend = $container->get('database')->driver();
        $default_backend = $container->get('database')->databaseType();
        $container->set('database', NULL);
      }
      catch (\Exception $e) {
        // If Drupal is not installed or a test doesn't define database there
        // is nothing to override.
        return;
      }
    }

    foreach ($container->findTaggedServiceIds('backend_overridable') as $id => $attributes) {
      // If the service is already an alias it is not the original backend, so
      // we don't want to fallback to other storages any longer.
      if ($container->hasAlias($id)) {
        continue;
      }
      if ($container->hasDefinition("$driver_backend.$id") || $container->hasAlias("$driver_backend.$id")) {
        $container->setAlias($id, new Alias("$driver_backend.$id"));
      }
      elseif ($container->hasDefinition("$default_backend.$id") || $container->hasAlias("$default_backend.$id")) {
        $container->setAlias($id, new Alias("$default_backend.$id"));
      }
    }
  }

}
