<?php

/**
 * @file
 * Contains \Drupal\edit\Access\EditEntityFieldAccessCheck.
 */

namespace Drupal\edit\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\edit\Access\EditEntityFieldAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\FieldInfo;
use Drupal\Core\Entity\EntityManager;

/**
 * Access check for editing entity fields.
 */
class EditEntityFieldAccessCheck implements StaticAccessCheckInterface, EditEntityFieldAccessCheckInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The field info.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * Constructs a EditEntityFieldAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info.
   */
  public function __construct(EntityManager $entity_manager, FieldInfo $field_info) {
    $this->entityManager = $entity_manager;
    $this->fieldInfo = $field_info;
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
  public function access(Route $route, Request $request) {
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
    if (!$field_name || !$this->fieldInfo->getInstance($entity->entityType(), $entity->bundle(), $field_name)) {
      throw new NotFoundHttpException();
    }
    $langcode = $request->attributes->get('langcode');
    if (!$langcode || (field_valid_language($langcode) !== $langcode)) {
      throw new NotFoundHttpException();
    }
  }

}
