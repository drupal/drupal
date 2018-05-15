<?php

namespace Drupal\Tests\rest\Functional\EntityResource\CommentType;

@trigger_error('The ' . __NAMESPACE__ . '\CommentTypeResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\comment\Functional\Rest\CommentTypeResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\comment\Functional\Rest\CommentTypeResourceTestBase as CommentTypeResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\comment\Functional\Rest\CommentTypeResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class CommentTypeResourceTestBase extends CommentTypeResourceTestBaseReal {
}
