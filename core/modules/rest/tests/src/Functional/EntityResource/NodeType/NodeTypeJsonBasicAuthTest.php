<?php

namespace Drupal\Tests\rest\Functional\EntityResource\NodeType;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group rest
 */
class NodeTypeJsonBasicAuthTest extends NodeTypeResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

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
