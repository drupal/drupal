<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes the super user access policy when toggled off.
 */
class SuperUserAccessPolicyPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if ($container->getParameter('security.enable_super_user') === FALSE) {
      $container->removeDefinition('access_policy.super_user');
      $container->removeAlias('Drupal\Core\Session\SuperUserAccessPolicy');
    }
  }

}
