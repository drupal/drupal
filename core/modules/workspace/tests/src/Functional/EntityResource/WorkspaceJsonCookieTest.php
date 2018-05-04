<?php

namespace Drupal\Tests\workspace\Functional\EntityResource;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * Test workspace entities for JSON requests with cookie authentication.
 *
 * @group workspace
 */
class WorkspaceJsonCookieTest extends WorkspaceResourceTestBase {

  use CookieResourceTestTrait;

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
  protected static $auth = 'cookie';

}
