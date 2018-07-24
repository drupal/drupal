<?php

namespace Drupal\Tests\workspace\Functional\EntityResource;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Test workspace entities for unauthenticated JSON requests.
 *
 * @group workspace
 */
class WorkspaceJsonAnonTest extends WorkspaceResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

}
