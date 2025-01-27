<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\Core\Database\DriverSpecificDatabaseTestBase;
use Drupal\mysql\Plugin\views\query\MysqlCastSql;

/**
 * Tests views service.
 *
 * @group Database
 */
class ViewsTest extends DriverSpecificDatabaseTestBase {

  /**
   * Tests views service.
   */
  public function testViewsService(): void {
    $this->assertFalse($this->container->has('views.cast_sql'));
    $this->enableModules(['views']);
    $this->assertInstanceOf(MysqlCastSql::class, $this->container->get('views.cast_sql'));
    $this->assertFalse($this->container->has('mysql.views.cast_sql'));
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $this->assertSame($container->hasDefinition('mysql.views.cast_sql'), isset($container->getParameter('container.modules')['views']));
  }

}
