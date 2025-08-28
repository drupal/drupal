<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Layout Builder Entity View Display Json Cookie.
 */
#[Group('layout_builder')]
#[Group('rest')]
class LayoutBuilderEntityViewDisplayJsonCookieTest extends LayoutBuilderEntityViewDisplayResourceTestBase {

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
