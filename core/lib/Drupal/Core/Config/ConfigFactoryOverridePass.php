<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigFactoryOverridePass.
 */

namespace Drupal\Core\Config;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds services to the config factory service.
 *
 * @see \Drupal\Core\Config\ConfigFactory
 * @see \Drupal\Core\Config\ConfigFactoryOverrideInterface
 */
class ConfigFactoryOverridePass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $manager = $container->getDefinition('config.factory');
    $services = array();
    foreach ($container->findTaggedServiceIds('config.factory.override') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $services[] = array('id' => $id, 'priority' => $priority);
    }
    usort($services, array($this, 'compareServicePriorities'));
    foreach ($services as $service) {
      $manager->addMethodCall('addOverride', array(new Reference($service['id'])));
    }
  }

  /**
   * Compares services by priority for ordering.
   *
   * @param array $a
   *   Service to compare.
   * @param array $b
   *   Service to compare.
   *
   * @return int
   *   Relative order of services to be used with usort. Higher priorities come
   *   first.
   */
  private function compareServicePriorities($a, $b) {
    if ($a['priority'] == $b['priority']) {
      return 0;
    }
    return ($a['priority'] > $b['priority']) ? -1 : 1;
  }

}
