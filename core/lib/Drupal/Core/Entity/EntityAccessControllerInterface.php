<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessControllerInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a common interface for entity access controller classes.
 */
interface EntityAccessControllerInterface {

  /**
   * Checks access to an operation on a given entity or entity translation.
   *
   * Use \Drupal\Core\Entity\EntityAccessControllerInterface::createAccess()
   * to check access to create an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "update" or "delete".
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   Language::LANGCODE_DEFAULT.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   *
   * @return bool
   *   TRUE if access was granted, FALSE otherwise.
   */
  public function access(EntityInterface $entity, $operation, $langcode = Language::LANGCODE_DEFAULT, AccountInterface $account = NULL);

  /**
   * Checks access to create an entity.
   *
   * @param string $entity_bundle
   *   (optional) The bundle of the entity. Required if the entity supports
   *   bundles, defaults to NULL otherwise.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   * @param array $context
   *   (optional) An array of key-value pairs to pass additional context when
   *   needed.
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array());

    /**
   * Clears all cached access checks.
   */
  public function resetCache();

  /**
   * Sets the module handler for this access controller.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   *
   * @return $this
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler);

  /**
   * Checks access to an operation on a given entity field.
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view" or "edit".
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *  (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   (optional) The field values for which to check access, or NULL if access
   *    is checked for the field definition, without any specific value
   *    available. Defaults to NULL.
   */
  public function fieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL);

}
