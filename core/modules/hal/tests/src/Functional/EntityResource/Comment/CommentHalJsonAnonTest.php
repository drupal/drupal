<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Comment;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class CommentHalJsonAnonTest extends CommentHalJsonTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   *
   * Anononymous users cannot edit their own comments.
   *
   * @see \Drupal\comment\CommentAccessControlHandler::checkAccess
   *
   * Therefore we grant them the 'administer comments' permission for the
   * purpose of this test. Then they are able to edit their own comments, but
   * some fields are still not editable, even with that permission.
   *
   * @see ::setUpAuthorization
   */
  protected static $patchProtectedFieldNames = [
    'changed',
    'thread',
    'entity_type',
    'field_name',
    'entity_id',
  ];

}
