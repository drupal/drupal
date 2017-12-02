<?php

namespace Drupal\path\Plugin\Field\FieldType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Represents a configurable entity path field.
 */
class PathFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    // Default the langcode to the current language if this is a new entity or
    // there is no alias for an existent entity.
    // @todo Set the langcode to not specified for untranslatable fields
    //   in https://www.drupal.org/node/2689459.
    $value = ['langcode' => $this->getLangcode()];

    $entity = $this->getEntity();
    if (!$entity->isNew()) {
      // @todo Support loading languge neutral aliases in
      //   https://www.drupal.org/node/2511968.
      $alias = \Drupal::service('path.alias_storage')->load([
        'source' => '/' . $entity->toUrl()->getInternalPath(),
        'langcode' => $this->getLangcode(),
      ]);

      if ($alias) {
        $value = $alias;
      }
    }

    $this->list[0] = $this->createItem(0, $value);
  }

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

}
