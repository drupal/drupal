<?php

declare(strict_types=1);

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass to merge moved classes into a single container parameter.
 */
class BackwardsCompatibilityClassLoaderPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $moved_classes = $container->hasParameter('core.moved_classes') ? $container->getParameter('core.moved_classes') : [];
    $modules = array_keys($container->getParameter('container.modules'));
    foreach ($modules as $module) {
      $parameter_name = $module . '.moved_classes';
      if ($container->hasParameter($parameter_name)) {
        $module_moved = $container->getParameter($parameter_name);
        \assert(is_array($module_moved));
        \assert(count($module_moved) === count(array_column($module_moved, 'class')), 'Missing class key for moved classes in ' . $module);
        $moved_classes = $moved_classes + $module_moved;
      }
    }
    if (!empty($moved_classes)) {
      $container->setParameter('moved_classes', $moved_classes);
    }
  }

}
