<?php

namespace Drupal\Tests\path_alias\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Test path_alias entities for unauthenticated JSON requests.
 *
 * @group path_alias
 */
class PathAliasJsonAnonTest extends PathAliasResourceTestBase {

  use AnonResourceTestTrait;

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
  protected $defaultTheme = 'stark';

}
