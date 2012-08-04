<?php

/**
 * @file
 * Definition of Drupal\Core\DependencyInjection\Container.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as BaseContainerBuilder;
use Symfony\Component\DependencyInjection\Container;

/**
 * Drupal's dependency injection container builder.
 *
 * @todo Submit upstream patches to Symfony to not require these overrides.
 */
class ContainerBuilder extends BaseContainerBuilder {

  /**
   * Overrides Symfony\Component\DependencyInjection\ContainerBuilder::addObjectResource().
   *
   * Drupal does not use Symfony's Config component, so we override
   * addObjectResource() with an empty implementation to prevent errors during
   * container compilation.
   */
  public function addObjectResource($object) {
  }

  /**
   * Overrides Symfony\Component\DependencyInjection\ContainerBuilder::set().
   *
   * Drupal's container builder can be used at runtime after compilation, so we
   * override Symfony's ContainerBuilder's restriction on setting services in a
   * frozen builder.
   *
   * @todo Restrict this to synthetic services only. Ideally, the upstream
   *   ContainerBuilder class should be fixed to allow setting synthetic
   *   services in a frozen builder.
   */
  public function set($id, $service, $scope = self::SCOPE_CONTAINER) {
    Container::set($id, $service, $scope);
  }

}
