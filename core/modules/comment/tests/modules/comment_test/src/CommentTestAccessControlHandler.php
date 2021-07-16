<?php

declare(strict_types = 1);

namespace Drupal\comment_test;

use Drupal\comment\CommentAccessControlHandler;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Testing comment access control handler.
 *
 * Wraps the original comment access control handler in order to capture the
 * passed context for testing purposes.
 *
 * @see \Drupal\Tests\comment\Kernel\CommentCreationAccessTest::testContextForCommentCreationAccessCheck()
 */
class CommentTestAccessControlHandler extends CommentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    \Drupal::state()->set('comment_test.create_access.context', $context);
    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

}
