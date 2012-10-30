<?php

/**
 * @file
 * Definition of Drupal\field_sql_storage\FieldSqlStorageBundle.
 */

namespace Drupal\field_sql_storage;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FieldSqlStorageBundle extends Bundle {

  public function build(ContainerBuilder $container) {
    $container
      ->register('entity.query.field_sql_storage', 'Drupal\field_sql_storage\Entity\QueryFactory')
      ->addArgument(new Reference('database'));
  }

}
