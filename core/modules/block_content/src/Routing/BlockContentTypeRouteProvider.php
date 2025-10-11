<?php

declare(strict_types=1);

namespace Drupal\block_content\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides HTML routes for block_content_type entities.
 */
class BlockContentTypeRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $routes = parent::getRoutes($entity_type);
    // Rename the entity.block_content_type.add_form route to keep BC.
    // @todo remove this and use an alias instead when https://www.drupal.org/project/drupal/issues/3506653 is done.
    $addFormRoute = $routes->get('entity.block_content_type.add_form');
    $routes->remove('entity.block_content_type.add_form');
    $routes->add('block_content.type_add', $addFormRoute);
    return $routes;
  }

}
