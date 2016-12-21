<?php

namespace Drupal\entity_test\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for test entities based on the core EntityController.
 */
class EntityTestEntityController extends EntityController {

  /**
   * {@inheritdoc}
   */
  public function addPage($entity_type_id) {
    $response = parent::addPage($entity_type_id);

    if ($response instanceof Response) {
      return $response;
    }
    foreach ($response['#bundles'] as $bundle) {
      $bundle['add_link']->getUrl()->setOption('attributes', [
        'class' => ['bundle-link'],
      ]);
    }
    return $response;
  }

}
