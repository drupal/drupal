<?php

namespace Drupal\Tests\hal\Functional\rdf;

use Drupal\Tests\rdf\Functional\Rest\RdfMappingResourceTestBase;
use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class RdfMappingHalJsonCookieTest extends RdfMappingResourceTestBase {

  use CookieResourceTestTrait;

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

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
