<?php

namespace Drupal\mysql;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\mysql\Plugin\views\query\MysqlCastSql;

/**
 * Registers the 'mysql.views.cast_sql' service when views is installed.
 */
class MysqlServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    if (isset($container->getParameter('container.modules')['views'])) {
      $container
        ->register('mysql.views.cast_sql', MysqlCastSql::class)
        ->setPublic(FALSE);
    }
  }

}
