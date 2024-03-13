<?php

namespace Drupal\Core\Update;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Service factory for the versioning update registry.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
 * \Drupal\Core\Update\UpdateHookRegistry instead.
 *
 * @see https://www.drupal.org/node/3423659
 */
class UpdateHookRegistryFactory implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Creates a new UpdateHookRegistry instance.
   *
   * @return \Drupal\Core\Update\UpdateHookRegistry
   *   The update registry instance.
   */
  public function create() {
    @trigger_error(__NAMESPACE__ . '\UpdateHookRegistryFactory is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Update\UpdateHookRegistry instead. See https://www.drupal.org/node/3423659', E_USER_DEPRECATED);
    return new UpdateHookRegistry(
      $this->container->get('module_handler')->getModuleList(),
      $this->container->get('keyvalue')
    );
  }

}
