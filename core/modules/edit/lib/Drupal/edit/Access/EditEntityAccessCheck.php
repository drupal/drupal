<?php

/**
 * @file
 * Contains \Drupal\edit\Access\EditEntityAccessCheck.
 */

namespace Drupal\edit\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for editing entities.
 */
class EditEntityAccessCheck implements AccessCheckInterface, EditEntityAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    // @see edit.routing.yml
    return array_key_exists('_access_edit_entity', $route->getRequirements());
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    // @todo Request argument validation and object loading should happen
    //   elsewhere in the request processing pipeline:
    //   http://drupal.org/node/1798214.
    $this->validateAndUpcastRequestAttributes($request);

    return $this->accessEditEntity($request->attributes->get('entity'));
  }

  /**
   * {@inheritdoc}
   */
  public function accessEditEntity(EntityInterface $entity) {
    return $entity->access('update');
  }

  /**
   * Validates and upcasts request attributes.
   */
  protected function validateAndUpcastRequestAttributes(Request $request) {
    // Load the entity.
    if (!is_object($entity = $request->attributes->get('entity'))) {
      $entity_id = $entity;
      $entity_type = $request->attributes->get('entity_type');
      if (!$entity_type || !entity_get_info($entity_type)) {
        throw new NotFoundHttpException();
      }
      $entity = entity_load($entity_type, $entity_id);
      if (!$entity) {
        throw new NotFoundHttpException();
      }
      $request->attributes->set('entity', $entity);
    }
  }

}
