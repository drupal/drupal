<?php

namespace Drupal\FunctionalTests\Hal;

use Drupal\FunctionalTests\Rest\EntityViewDisplayResourceTestBase;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 */
class EntityViewDisplayHalJsonAnonTest extends EntityViewDisplayResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

}
