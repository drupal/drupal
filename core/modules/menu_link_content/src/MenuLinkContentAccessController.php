<?php
/**
 * @file
 * Contains \Drupal\menu_link_content\MenuLinkContentAccessController.
 */

namespace Drupal\menu_link_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the user entity type.
 */
class MenuLinkContentAccessController extends EntityAccessController {

  /**
   * The access manager to check routes by name.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        // There is no direct view.
        return FALSE;

      case 'update':
        // If there is a URL, this is an external link so always accessible.
        return $account->hasPermission('administer menu') && ($entity->getUrl() || $this->accessManager()->checkNamedRoute($entity->getRouteName(), $entity->getRouteParameters(), $account));

      case 'delete':
        return !$entity->isNew() && $account->hasPermission('administer menu');
    }
  }

  /**
   * Returns the access manager.
   *
   * @return \Drupal\Core\Access\AccessManager
   *   The route provider.
   */
  protected function accessManager() {
    if (!$this->accessManager) {
      $this->accessManager = \Drupal::service('access_manager');
    }
    return $this->accessManager;
  }
}
