<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Comment;

@trigger_error('The ' . __NAMESPACE__ . '\CommentResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\comment\Functional\Rest\CommentResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\comment\Functional\Rest\CommentResourceTestBase as CommentResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\comment\Functional\Rest\CommentResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class CommentResourceTestBase extends CommentResourceTestBaseReal {
}
