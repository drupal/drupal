<?php

/**
 * @file
 * Contains \Drupal\quickedit\Access\EditEntityAccessCheck.
 */

namespace Drupal\quickedit\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for editing entities with QuickEdit.
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
   * Checks Quick Edit access to the entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   *
   * @todo Replace $request parameter with $entity once
   *   https://drupal.org/node/1837388 is fixed.
   */
  public function access(Request $request, AccountInterface $account) {
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
   *
   * @todo Remove once https://drupal.org/node/1837388 is fixed.
   */
  protected function validateAndUpcastRequestAttributes(Request $request) {
    // Load the entity.
    if (!is_object($entity = $request->attributes->get('entity'))) {
      $entity_id = $entity;
      $entity_type = $request->attributes->get('entity_type');
      if (!$entity_type || !$this->entityManager->getDefinition($entity_type)) {
        return FALSE;
      }
      $entity = $this->entityManager->getStorage($entity_type)->load($entity_id);
      if (!$entity) {
        return FALSE;
      }
      $request->attributes->set('entity', $entity);
    }

    return TRUE;
  }

}
