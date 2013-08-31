<?php

/**
 * @file
 * Contains \Drupal\edit\Access\EditEntityAccessCheck.
 */

namespace Drupal\edit\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * Access check for editing entities.
 */
class EditEntityAccessCheck implements StaticAccessCheckInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a EditEntityAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    // @see edit.routing.yml
    return array('_access_edit_entity');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    // @todo Request argument validation and object loading should happen
    //   elsewhere in the request processing pipeline:
    //   http://drupal.org/node/1798214.
    $this->validateAndUpcastRequestAttributes($request);

    return $this->accessEditEntity($request->attributes->get('entity'))  ? static::ALLOW : static::DENY;
  }

  /**
   * {@inheritdoc}
   */
  protected function accessEditEntity(EntityInterface $entity) {
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
      if (!$entity_type || !$this->entityManager->getDefinition($entity_type)) {
        throw new NotFoundHttpException();
      }
      $entity = $this->entityManager->getStorageController($entity_type)->load($entity_id);
      if (!$entity) {
        throw new NotFoundHttpException();
      }
      $request->attributes->set('entity', $entity);
    }
  }

}
