<?php

declare(strict_types=1);

namespace Drupal\Tests\responsive_image\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Responsive Image Style Json Basic Auth.
 */
#[Group('rest')]
class ResponsiveImageStyleJsonBasicAuthTest extends ResponsiveImageStyleResourceTestBase {

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
