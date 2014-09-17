<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityAccessControlHandlerInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for entity access control handlers.
 */
interface EntityAccessControlHandlerInterface {

  /**
   * Checks access to an operation on a given entity or entity translation.
   *
   * Use \Drupal\Core\Entity\EntityAccessControlHandlerInterface::createAccess()
   * to check access to create an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "update" or "delete".
   * @param string $langcode
   *   (optional) The language code for which to check access. Defaults to
   *   LanguageInterface::LANGCODE_DEFAULT.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function access(EntityInterface $entity, $operation, $langcode = LanguageInterface::LANGCODE_DEFAULT, AccountInterface $account = NULL, $return_as_object = FALSE);

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
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array(), $return_as_object = FALSE);

  /**
   * Clears all cached access checks.
   */
  public function resetCache();

  /**
   * Sets the module handler for this access control handler.
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
   * This method does not determine whether access is granted to the entity
   * itself, only the specific field. Callers are responsible for ensuring that
   * entity access is also respected, for example by using
   * \Drupal\Core\Entity\EntityAccessControlHandlerInterface::access().
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
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   *
   * @see \Drupal\Core\Entity\EntityAccessControlHandlerInterface::access()
   */
  public function fieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL, $return_as_object = FALSE);

}
