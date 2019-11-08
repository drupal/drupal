<?php

namespace Drupal\FunctionalTests\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group rest
 */
class BaseFieldOverrideJsonBasicAuthTest extends BaseFieldOverrideResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

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
