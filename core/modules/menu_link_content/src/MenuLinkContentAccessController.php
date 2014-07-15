<?php
/**
 * @file
 * Contains \Drupal\menu_link_content\MenuLinkContentAccessController.
 */

namespace Drupal\menu_link_content;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access controller for the user entity type.
 */
class MenuLinkContentAccessController extends EntityAccessController implements EntityControllerInterface {

  /**
   * The access manager to check routes by name.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * Creates a new MenuLinkContentAccessController.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The access manager to check routes by name.
   */
  public function __construct(EntityTypeInterface $entity_type, AccessManager $access_manager) {
    parent::__construct($entity_type);

    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type, $container->get('access_manager'));
  }

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
        return $account->hasPermission('administer menu') && ($entity->getUrl() || $this->accessManager->checkNamedRoute($entity->getRouteName(), $entity->getRouteParameters(), $account));

      case 'delete':
        return !$entity->isNew() && $account->hasPermission('administer menu');
    }
  }

}
