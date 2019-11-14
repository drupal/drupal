<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Comment;

@trigger_error('The ' . __NAMESPACE__ . '\CommentHalJsonTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\comment\Functional\Hal\CommentHalJsonTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\comment\Functional\Hal\CommentHalJsonTestBase as CommentHalJsonTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\comment\Functional\Hal\CommentHalJsonTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class CommentHalJsonTestBase extends CommentHalJsonTestBaseReal {
}
