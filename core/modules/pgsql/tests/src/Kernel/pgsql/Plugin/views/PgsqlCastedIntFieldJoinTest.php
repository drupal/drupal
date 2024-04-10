<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql\Plugin\views;

use Drupal\Tests\views\Kernel\Plugin\CastedIntFieldJoinTestBase;

/**
 * Tests PostgreSQL specific cast handling.
 *
 * @group Database
 */
class PgsqlCastedIntFieldJoinTest extends CastedIntFieldJoinTestBase {

  /**
   * The db type that should be used for casting fields as integers.
   */
  protected string $castingType = 'INTEGER';

}
