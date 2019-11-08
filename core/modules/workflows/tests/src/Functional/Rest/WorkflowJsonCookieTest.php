<?php

namespace Drupal\Tests\workflows\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group rest
 */
class WorkflowJsonCookieTest extends WorkflowResourceTestBase {

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
