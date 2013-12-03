<?php

/**
 * @file
 * Contains \Drupal\edit\Access\EditEntityFieldAccessCheck.
 */

namespace Drupal\edit\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\edit\Access\EditEntityFieldAccessCheckInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for editing entity fields.
 */
class EditEntityFieldAccessCheck implements StaticAccessCheckInterface, EditEntityFieldAccessCheckInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EditEntityFieldAccessCheck object.
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
  public function appliesTo() {
    return array('_access_edit_entity_field');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // @todo Request argument validation and object loading should happen
    //   elsewhere in the request processing pipeline:
    //   http://drupal.org/node/1798214.
    $this->validateAndUpcastRequestAttributes($request);

    return $this->accessEditEntityField($request->attributes->get('entity'), $request->attributes->get('field_name'))  ? static::ALLOW : static::DENY;
  }

  /**
   * {@inheritdoc}
   */
  public function accessEditEntityField(EntityInterface $entity, $field_name) {
    return $entity->access('update') && $entity->get($field_name)->access('edit');
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

    // Validate the field name and language.
    $field_name = $request->attributes->get('field_name');
    if (!$field_name || !$entity->hasField($field_name)) {
      throw new NotFoundHttpException();
    }
    $langcode = $request->attributes->get('langcode');
    if (!$langcode || (field_valid_language($langcode) !== $langcode)) {
      throw new NotFoundHttpException();
    }
  }

}
