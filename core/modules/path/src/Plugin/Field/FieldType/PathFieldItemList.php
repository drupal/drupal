<?php

namespace Drupal\path\Plugin\Field\FieldType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;

/**
 * Represents a configurable entity path field.
 */
class PathFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'view') {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIfHasPermissions($account, ['create url aliases', 'administer url aliases'], 'OR')->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete all aliases associated with this entity in the current language.
    $entity = $this->getEntity();
    $conditions = [
      'source' => '/' . $entity->toUrl()->getInternalPath(),
      'langcode' => $entity->language()->getId(),
    ];
    \Drupal::service('path.alias_storage')->delete($conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $this->ensureLoaded();
    return parent::getValue($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureLoaded();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->ensureLoaded();
    return parent::getIterator();
  }

  /**
   * Automatically create the first item for computed fields.
   *
   * This ensures that ::getValue() and ::isEmpty() calls will behave like a
   * non-computed field.
   *
   * @todo: Move this to the base class in https://www.drupal.org/node/2392845.
   */
  protected function ensureLoaded() {
    if (!isset($this->list[0]) && $this->definition->isComputed()) {
      $this->list[0] = $this->createItem(0);
    }
  }

}
