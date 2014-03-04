<?php

/**
 * @file
 * Contains \Drupal\entity_test\Controller\EntityTestController.
 */

namespace Drupal\entity_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for entity_test routes.
 */
class EntityTestController extends ControllerBase {

  /**
   * Displays the 'Add new entity_test' form.
   *
   * @param string $entity_type
   *   Name of the entity type for which a create form should be displayed.
   *
   * @return array
   *   The processed form for a new entity_test.
   *
   * @see \Drupal\entity_test\Routing\EntityTestRoutes::routes()
   */
  public function testAdd($entity_type) {
    $entity = entity_create($entity_type, array());
    $form = $this->entityFormBuilder()->getForm($entity);
    $form['#title'] = $this->t('Create an @type', array('@type' => $entity_type));
    return $form;
  }

  /**
   * Displays the 'Edit existing entity_test' form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object to get entity type from.
   *
   * @return array
   *   The processed form for the edited entity.
   *
   * @see \Drupal\entity_test\Routing\EntityTestRoutes::routes()
   */
  public function testEdit(Request $request) {
    $entity = $request->attributes->get($request->attributes->get('_entity_type'));
    $form = $this->entityFormBuilder()->getForm($entity);
    $form['#title'] = $entity->label();
    return $form;
  }

  /**
   * Returns an empty page.
   *
   * @see \Drupal\entity_test\Routing\EntityTestRoutes::routes()
   */
  public function testAdmin() {
    return '';
  }

}
