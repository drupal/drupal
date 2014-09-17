<?php

/**
 * @file
 * Contains \Drupal\node\Access\NodePreviewAccessCheck.
 */

namespace Drupal\node\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Determines access to node previews.
 */
class NodePreviewAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Checks access to the node preview page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\NodeInterface $node_preview
   *   The node that is being previewed.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, NodeInterface $node_preview) {
    if ($node_preview->isNew()) {
      $access_controller = $this->entityManager->getAccessControlHandler('node');
      return $access_controller->createAccess($node_preview->bundle(), $account, [], TRUE);
    }
    else {
      return $node_preview->access('update', $account, TRUE);
    }
  }

}
