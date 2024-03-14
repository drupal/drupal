<?php

declare(strict_types=1);

namespace Drupal\Tests\action\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group action
 * @group legacy
 */
class ActionJsonBasicAuthTest extends ActionResourceTestBase {

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
