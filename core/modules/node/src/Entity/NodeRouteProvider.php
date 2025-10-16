<?php

namespace Drupal\node\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\node\Controller\NodeController;
use Drupal\node\Controller\NodeViewController;

/**
 * Provides routes for nodes.
 */
class NodeRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $routes = parent::getRoutes($entity_type);
    // Rename the entity.node.add_form and entity.node.add_page routes to keep
    // BC.
    // @todo remove this and use an alias instead when https://www.drupal.org/project/drupal/issues/3506653 is done.
    $addPageRoute = $routes->get('entity.node.add_page');
    $routes->remove('entity.node.add_page');
    $routes->add('node.add_page', $addPageRoute);

    $addFormRoute = $routes->get('entity.node.add_form');
    $routes->remove('entity.node.add_form');
    $routes->add('node.add', $addFormRoute);
    return $routes;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddPageRoute($entity_type)) {
      return $route
        ->setDefault('_controller', NodeController::class . '::addPage')
        ->setDefault('_title', 'Add content')
        ->setOption('_node_operation_route', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddFormRoute($entity_type)) {
      return $route
        ->setDefault('_title_callback', NodeController::class . '::addPageTitle')
        ->setOption('_node_operation_route', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCanonicalRoute($entity_type)) {
      return $route->setDefault('_controller', NodeViewController::class . '::view');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getEditFormRoute($entity_type)) {
      return $route->setOption('_node_operation_route', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getDeleteFormRoute($entity_type)) {
      return $route->setOption('_node_operation_route', TRUE);
    }
  }

}
