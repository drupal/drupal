<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * Test path_alias entities for JSON requests with cookie authentication.
 *
 * @group path_alias
 */
class PathAliasJsonCookieTest extends PathAliasResourceTestBase {

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

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
