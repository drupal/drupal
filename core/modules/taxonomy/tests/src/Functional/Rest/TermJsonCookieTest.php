<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Term Json Cookie.
 */
#[Group('rest')]
#[Group('#slow')]
class TermJsonCookieTest extends TermResourceTestBase {

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
