<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Kernel\sqlite\Plugin\views;

use Drupal\Tests\views\Kernel\Plugin\CastedIntFieldJoinTestBase;

/**
 * Tests SQLite specific cast handling.
 *
 * @group Database
 */
class SqliteCastedIntFieldJoinTest extends CastedIntFieldJoinTestBase {

  /**
   * The db type that should be used for casting fields as integers.
   */
  protected string $castingType = 'INTEGER';

}
