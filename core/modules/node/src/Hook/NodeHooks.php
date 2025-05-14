<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\user\UserInterface;

/**
 * Hook implementations for the node module.
 */
class NodeHooks {

  /**
   * The Node Storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected NodeStorageInterface $nodeStorage;

  /**
   * NodeHooks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
  }

  /**
   * Implements hook_user_cancel().
   *
   * Unpublish nodes (current revisions).
   */
  #[Hook('user_cancel')]
  public function userCancelBlockUnpublish($edit, UserInterface $account, $method): void {
    if ($method === 'user_cancel_block_unpublish') {
      $nids = $this->nodeStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $account->id())
        ->execute();
      $this->moduleHandler->invoke('node', 'mass_update', [$nids, ['status' => 0], NULL, TRUE]);
    }
  }

  /**
   * Implements hook_user_cancel().
   *
   * Anonymize all of the nodes for this old account.
   */
  #[Hook('user_cancel')]
  public function userCancelReassign($edit, UserInterface $account, $method): void {
    if ($method === 'user_cancel_reassign') {
      $vids = $this->nodeStorage->userRevisionIds($account);
      $this->moduleHandler->invoke('node', 'mass_update', [$vids, ['uid' => 0, 'revision_uid' => 0], NULL, TRUE, TRUE]);
    }
  }

  /**
   * Implements hook_block_alter().
   */
  #[Hook('block_alter')]
  public function blockAlter(&$definitions): void {
    // Hide the deprecated Syndicate block from the UI.
    $definitions['node_syndicate_block']['_block_ui_hidden'] = TRUE;
  }

}
