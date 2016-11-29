<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Comment;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class CommentHalJsonCookieTest extends CommentHalJsonTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
