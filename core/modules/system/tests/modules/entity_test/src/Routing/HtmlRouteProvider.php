<?php

namespace Drupal\entity_test\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\entity_test\Controller\EntityTestEntityController;

/**
 * Route provider for test entities.
 */
class HtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    return parent::getAddPageRoute($entity_type)
      ->setDefault('_controller', EntityTestEntityController::class . '::addPage')
      ->setOption('_admin_route', TRUE);
  }

}
