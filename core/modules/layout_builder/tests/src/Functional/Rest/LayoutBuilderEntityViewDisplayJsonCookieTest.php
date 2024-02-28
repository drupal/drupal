<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group layout_builder
 * @group rest
 */
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
