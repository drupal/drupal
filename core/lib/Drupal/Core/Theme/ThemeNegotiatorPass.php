<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeNegotiatorPass.
 */

namespace Drupal\Core\Theme;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds services to the theme negotiator service.
 *
 * @see \Drupal\Core\Theme\ThemeNegotiator
 * @see \Drupal\Core\Theme\ThemeNegotiatorInterfa
 */
class ThemeNegotiatorPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('theme.negotiator')) {
      return;
    }
    $manager = $container->getDefinition('theme.negotiator');
    foreach ($container->findTaggedServiceIds('theme_negotiator') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $manager->addMethodCall('addNegotiator', array(new Reference($id), $priority));
    }
  }

}
