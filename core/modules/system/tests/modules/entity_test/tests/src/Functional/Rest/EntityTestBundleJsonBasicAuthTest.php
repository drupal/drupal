<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_test\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests EntityTest Bundle Json Basic Auth.
 */
#[Group('rest')]
class EntityTestBundleJsonBasicAuthTest extends EntityTestBundleResourceTestBase {

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
