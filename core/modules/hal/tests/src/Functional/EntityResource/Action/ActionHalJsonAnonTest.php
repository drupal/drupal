<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Action;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\Action\ActionResourceTestBase;

/**
 * @group hal
 */
class ActionHalJsonAnonTest extends ActionResourceTestBase {

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
