<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel;

use Drupal\Core\Entity\Query\Sql\QueryFactory as BaseQueryFactory;
use Drupal\Core\Entity\Query\Sql\pgsql\QueryFactory as DeprecatedQueryFactory;
use Drupal\KernelTests\KernelTestBase;
use Drupal\pgsql\EntityQuery\QueryFactory;

/**
 * Tests the move of the 'pgsql.entity.query.sql' service.
 */
class EntityQueryServiceDeprecation extends KernelTestBase {

  /**
   * Tests that the core provided service is deprecated.
   *
   * @group legacy
   */
  public function testPostgresServiceDeprecated(): void {
    $running_driver = $this->container->get('database')->driver();
    if ($running_driver === 'pgsql') {
      $this->markTestSkipped('The service is not deprecated for pgsql database driver.');
    }
    $this->expectDeprecation('The "pgsql.entity.query.sql" service is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Install the pgsql module to replace this service. See https://www.drupal.org/node/3488580');
    $this->expectDeprecation('\Drupal\Core\Entity\Query\Sql\pgsql\QueryFactory is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. The PostgreSQL override of the entity query has been moved to the pgsql module. See https://www.drupal.org/node/3488580');
    $service = $this->container->get('pgsql.entity.query.sql');
    $this->assertInstanceOf(DeprecatedQueryFactory::class, $service);
  }

  /**
   * Tests that the pgsql provided service is not deprecated.
   */
  public function testPostgresServiceNotDeprecated(): void {
    $running_driver = $this->container->get('database')->driver();
    if ($running_driver !== 'pgsql') {
      $this->markTestSkipped('The service is deprecated for database drivers other than pgsql.');
    }
    $service = $this->container->get('pgsql.entity.query.sql');
    $this->assertInstanceOf(QueryFactory::class, $service);
  }

  /**
   * Tests getting the backend overridden service does not trigger deprecations.
   */
  public function testFactoryOverriddenService(): void {
    $service = $this->container->get('entity.query.sql');
    $this->assertInstanceOf(BaseQueryFactory::class, $service);
  }

}
