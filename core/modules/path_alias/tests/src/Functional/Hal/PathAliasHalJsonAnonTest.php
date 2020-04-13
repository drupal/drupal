<?php

namespace Drupal\Tests\path_alias\Functional\Hal;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group hal
 * @group path_alias
 */
class PathAliasHalJsonAnonTest extends PathAliasHalJsonTestBase {

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
