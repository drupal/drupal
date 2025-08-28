<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Role Json Cookie.
 */
#[Group('rest')]
class RoleJsonCookieTest extends RoleResourceTestBase {

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
