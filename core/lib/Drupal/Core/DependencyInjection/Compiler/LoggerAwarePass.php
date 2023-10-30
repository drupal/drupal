<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Psr\Log\LoggerAwareInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Sets the logger on all services that implement LoggerAwareInterface.
 */
class LoggerAwarePass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $interface = LoggerAwareInterface::class;
    foreach ($container->findTaggedServiceIds('logger_aware') as $id => $attributes) {
      $definition = $container->getDefinition($id);
      // Skip services that are already calling setLogger().
      if ($definition->hasMethodCall('setLogger')) {
        continue;
      }
      if (!is_subclass_of($definition->getClass(), $interface)) {
        throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
      }
      $providerTag = $definition->getTag('_provider');
      $loggerId = 'logger.channel.' . $providerTag[0]['provider'];
      if ($container->has($loggerId)) {
        $definition->addMethodCall('setLogger', [new Reference($loggerId)]);
      }
    }
  }

}
