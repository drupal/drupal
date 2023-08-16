<?php

namespace Drupal\Tests\mysql\Kernel\mysql\Plugin\views;

use Drupal\Tests\views\Kernel\Plugin\CastedIntFieldJoinTestBase;

/**
 * Tests MySQL specific cast handling.
 *
 * @group Database
 */
class MySqlCastedIntFieldJoinTest extends CastedIntFieldJoinTestBase {

  /**
   * The db type that should be used for casting fields as integers.
   */
  protected string $castingType = 'UNSIGNED';

}
