<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Filter Format Json Basic Auth.
 */
#[Group('rest')]
class FilterFormatJsonBasicAuthTest extends FilterFormatResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $auth = 'basic_auth';

}
