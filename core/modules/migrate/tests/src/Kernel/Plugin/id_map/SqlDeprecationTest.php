<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel\Plugin\id_map;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\migrate\id_map\Sql;

/**
 * Tests deprecation notice in Sql constructor.
 *
 * @group migrate
 * @group legacy
 */
class SqlDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate'];

  /**
   * @covers \Drupal\migrate\Plugin\migrate\id_map\Sql::__construct
   */
  public function testOptionalParametersDeprecation(): void {
    $migration = $this->prophesize('\Drupal\migrate\Plugin\MigrationInterface')->reveal();
    $this->expectDeprecation('Calling Sql::__construct() without the $migration_manager argument is deprecated in drupal:9.5.0 and the $migration_manager argument will be required in drupal:11.0.0. See https://www.drupal.org/node/3277306');
    new Sql(
      [],
      'sql',
      [],
      $migration,
      $this->container->get('event_dispatcher')
    );
  }

}
