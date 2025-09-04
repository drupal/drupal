<?php

declare(strict_types=1);

namespace Drupal\Tests\config_test\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests ConfigTest Json Cookie.
 */
#[Group('rest')]
class ConfigTestJsonCookieTest extends ConfigTestResourceTestBase {

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
