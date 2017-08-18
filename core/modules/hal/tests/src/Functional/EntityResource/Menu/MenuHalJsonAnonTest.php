<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Menu;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\Menu\MenuResourceTestBase;

/**
 * @group hal
 */
class MenuHalJsonAnonTest extends MenuResourceTestBase {

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
