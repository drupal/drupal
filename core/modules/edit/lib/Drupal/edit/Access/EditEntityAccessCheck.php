<?php

/**
 * @file
 * Contains \Drupal\edit\Access\EditEntityAccessCheck.
 */

namespace Drupal\edit\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for editing entities.
 */
class EditEntityAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EditEntityAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // @todo Request argument validation and object loading should happen
    //   elsewhere in the request processing pipeline:
    //   http://drupal.org/node/1798214.
    if (!$this->validateAndUpcastRequestAttributes($request)) {
      return static::KILL;
    }

    return $this->accessEditEntity($request->attributes->get('entity'), $account)  ? static::ALLOW : static::DENY;
  }

  /**
   * {@inheritdoc}
   */
  protected function accessEditEntity(EntityInterface $entity, $account) {
    return $entity->access('update', $account);
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
        return FALSE;
      }
      $entity = $this->entityManager->getStorageController($entity_type)->load($entity_id);
      if (!$entity) {
        return FALSE;
      }
      $request->attributes->set('entity', $entity);
    }

    return TRUE;
  }

}
