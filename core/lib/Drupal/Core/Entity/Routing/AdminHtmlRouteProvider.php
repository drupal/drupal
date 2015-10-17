<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider.
 */

namespace Drupal\Core\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides HTML routes for entities with administrative edit/delete pages.
 *
 * Use this class if the edit and delete form routes should use the
 * administrative theme.
 *
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider.
 *
 * @internal
 */
class AdminHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getEditFormRoute($entity_type)) {
      $route->setOption('_admin_route', TRUE);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getDeleteFormRoute($entity_type)) {
      $route->setOption('_admin_route', TRUE);
      return $route;
    }
  }

}
