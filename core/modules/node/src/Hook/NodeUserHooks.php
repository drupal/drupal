<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeBulkUpdate;
use Drupal\user\UserInterface;

/**
 * Hook implementations for the node module.
 */
class NodeUserHooks {

  /**
   * NodeHooks constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected NodeBulkUpdate $nodeBulkUpdate,
  ) {
  }

  /**
   * Implements hook_user_cancel().
   *
   * Unpublish nodes (current revisions).
   */
  #[Hook('user_cancel')]
  public function userCancelBlockUnpublish($edit, UserInterface $account, $method): void {
    if ($method === 'user_cancel_block_unpublish') {
      $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $account->id())
        ->execute();
      $this->nodeBulkUpdate->process($nids, ['status' => 0], NULL, TRUE);
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
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->allRevisions()
        ->accessCheck(FALSE)
        ->condition('uid', $account->id());
      $vids = array_keys($query->execute());
      $this->nodeBulkUpdate->process($vids, ['uid' => 0, 'revision_uid' => 0], NULL, TRUE, TRUE);
    }
  }

}
