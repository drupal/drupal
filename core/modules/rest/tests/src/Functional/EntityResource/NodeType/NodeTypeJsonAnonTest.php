<?php

namespace Drupal\Tests\rest\Functional\EntityResource\NodeType;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class NodeTypeJsonAnonTest extends NodeTypeResourceTestBase {

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
