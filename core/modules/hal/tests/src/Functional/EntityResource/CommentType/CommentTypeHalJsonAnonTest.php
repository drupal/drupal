<?php

namespace Drupal\Tests\hal\Functional\EntityResource\CommentType;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\CommentType\CommentTypeResourceTestBase;

/**
 * @group hal
 */
class CommentTypeHalJsonAnonTest extends CommentTypeResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

}
