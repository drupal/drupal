<?php

namespace Drupal\Core\Update;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Removes services with unmet dependencies.
 *
 * Updates can install new modules that add services that existing services now
 * depend on. This compiler pass allows the update system to work in such cases.
 */
class UpdateCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $process_aliases = FALSE;
    // Loop over the defined services and remove any with unmet dependencies.
    // The kernel cannot be booted if the container has such services. This
    // allows modules to run their update hooks to enable newly added
    // dependencies.
    do {
      $has_changed = FALSE;
      foreach ($container->getDefinitions() as $key => $definition) {
        foreach ($definition->getArguments() as $argument) {
          if ($argument instanceof Reference) {
            $argument_id = (string) $argument;
            if (!$container->has($argument_id) && $argument->getInvalidBehavior() === ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
              // If the container does not have the argument and would throw an
              // exception, remove the service.
              $container->removeDefinition($key);
              $container->log($this, sprintf('Removed service "%s"; reason: depends on non-existent service "%s".', $key, $argument_id));
              $has_changed = TRUE;
              $process_aliases = TRUE;
            }
          }
        }
      }
      // Repeat if services have been removed.
    } while ($has_changed);

    // Remove aliases to services that have been removed. This does not need to
    // be part of the loop above because references to aliases have already been
    // resolved by Symfony's ResolveReferencesToAliasesPass.
    if ($process_aliases) {
      foreach ($container->getAliases() as $key => $alias) {
        $id = (string) $alias;
        if (!$container->has($id)) {
          $container->removeAlias($key);
          $container->log($this, sprintf('Removed alias "%s"; reason: alias to non-existent service "%s".', $key, $id));
        }
      }
    }
  }

}
