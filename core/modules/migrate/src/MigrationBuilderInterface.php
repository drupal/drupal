<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrationBuilderInterface.
 */

namespace Drupal\migrate;

/**
 * The migration builder interface.
 */
interface MigrationBuilderInterface {

  /**
   * Builds migration entities from templates.
   *
   * @param array $templates
   *   The parsed templates (each of which is an array parsed from YAML), keyed
   *   by ID.
   *
   * @return \Drupal\migrate\Entity\MigrationInterface[]
   *   The migration entities derived from the templates.
   */
  public function createMigrations(array $templates);

}
