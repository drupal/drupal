<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Comment;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\JsonBasicAuthWorkaroundFor2805281Trait;

/**
 * @group rest
 */
class CommentJsonBasicAuthTest extends CommentResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

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
   */
  protected static $expectedErrorMimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

  // @todo Fix in https://www.drupal.org/node/2805281: remove this trait usage.
  use JsonBasicAuthWorkaroundFor2805281Trait {
    JsonBasicAuthWorkaroundFor2805281Trait::assertResponseWhenMissingAuthentication insteadof BasicAuthResourceTestTrait;
  }

}
