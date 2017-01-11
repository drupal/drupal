<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Comment;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class CommentJsonAnonTest extends CommentResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   *
   * Anononymous users cannot edit their own comments.
   *
   * @see \Drupal\comment\CommentAccessControlHandler::checkAccess
   *
   * Therefore we grant them the 'administer comments' permission for the
   * purpose of this test.
   *
   * @see ::setUpAuthorization
   */
  protected static $patchProtectedFieldNames = [
    'pid',
    'entity_id',
    'changed',
    'thread',
    'entity_type',
    'field_name',
  ];

}
