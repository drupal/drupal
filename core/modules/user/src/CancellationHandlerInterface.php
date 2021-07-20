<?php

namespace Drupal\user;

/**
 * Defines an interface for entity types to react to user account cancellation.
 */
interface CancellationHandlerInterface {

  /**
   * The "block" cancellation method.
   *
   * With this cancellation method, the user account is blocked, but not
   * otherwise changed.
   *
   * @var string
   */
  const METHOD_BLOCK = 'user_cancel_block';

  /**
   * The "block and unpublish" cancellation method.
   *
   * With this cancellation method, the user account is blocked and the
   * current revision of any entity associated with the account is unpublished.
   *
   * @var string
   */
  const METHOD_BLOCK_UNPUBLISH = 'user_cancel_block_unpublish';

  /**
   * The "reassign" cancellation method.
   *
   * With this cancellation method, the user account is deleted and all
   * revisions of any entity associated with it are reassigned to the anonymous
   * user.
   *
   * @var string
   */
  const METHOD_REASSIGN = 'user_cancel_reassign';

  /**
   * The "delete" cancellation method.
   *
   * With this cancellation method, the user account is deleted. Modules may
   * implement entity hooks to react to the deletion as they see fit; it is
   * assumed that all entities associated with the account will be deleted.
   *
   * @var string
   */
  const METHOD_DELETE = 'user_cancel_delete';

  /**
   * Reacts to user account cancellation.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account being cancelled.
   * @param string $method
   *   The cancellation method. Will be one of the static::METHOD_* constants,
   *   or a custom string defined by an implementation of
   *   hook_user_cancel_methods().
   */
  public function cancelAccount(UserInterface $account, string $method): void;

}
