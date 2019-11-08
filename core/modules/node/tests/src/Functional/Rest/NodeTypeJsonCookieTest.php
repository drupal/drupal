<?php

namespace Drupal\Tests\node\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group rest
 */
class NodeTypeJsonCookieTest extends NodeTypeResourceTestBase {

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
