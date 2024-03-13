<?php

namespace Drupal\Core\Update;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Service factory for the update registry.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
 * \Drupal\Core\Update\UpdateRegistry instead.
 *
 * @see https://www.drupal.org/node/3423659
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
    @trigger_error(__NAMESPACE__ . '\UpdateHookRegistryFactory is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Update\UpdateRegistry instead. See https://www.drupal.org/node/3423659', E_USER_DEPRECATED);
    return new UpdateRegistry(
      $this->container->getParameter('app.root'),
      $this->container->getParameter('site.path'),
      $this->container->get('module_handler')->getModuleList(),
      $this->container->get('keyvalue')->get('post_update'),
      $this->container->get('theme_handler'),

    );
  }

}
