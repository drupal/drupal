<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Comment;

use Drupal\Tests\comment\Functional\Hal\CommentHalJsonTestBase as CommentHalJsonTestBaseReal;

/**
 * Class for backward compatibility. It is deprecated in Drupal 8.6.x.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class CommentHalJsonTestBase extends CommentHalJsonTestBaseReal {
}
