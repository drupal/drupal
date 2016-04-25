<?php

namespace Drupal\migrate;


interface MigrateBuildDependencyInterface {

  /**
   * Builds a dependency tree for the migrations and set their order.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
   *   Array of loaded migrations with their declared dependencies.
   * @param array $dynamic_ids
   *   Keys are dynamic ids (for example node:*) values are a list of loaded
   *   migration ids (for example node:page, node:article).
   *
   * @return array
   *   An array of migrations.
   */
  public function buildDependencyMigration(array $migrations, array $dynamic_ids);
}
