<?php

namespace Drupal\migration_provider_test\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * A test source plugin without a source_module.
 *
 * @MigrateSource(
 *   id = "no_source_module",
 * )
 */
class NoSourceModule extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    throw new \BadMethodCallException('This method should never be called');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    throw new \BadMethodCallException('This method should never be called');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    throw new \BadMethodCallException('This method should never be called');
  }

}
