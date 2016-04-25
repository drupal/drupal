<?php

namespace Drupal\Core\Update;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Service factory for the update registry.
 */
class UpdateRegistryFactory implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Creates a new UpdateRegistry instance.
   *
   * @return \Drupal\Core\Update\UpdateRegistry
   *   The update registry instance.
   */
  public function create() {
    return new UpdateRegistry($this->container->get('app.root'), $this->container->get('site.path'), array_keys($this->container->get('module_handler')->getModuleList()), $this->container->get('keyvalue')->get('post_update'));
  }

}
