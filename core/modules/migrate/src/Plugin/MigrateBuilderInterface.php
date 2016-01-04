<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrateBuilderInterface.
 */

namespace Drupal\migrate\Plugin;

/**
 * Defines the builder plugin type.
 *
 * Builder plugins implement custom logic to generate migration entities from
 * migration templates. For example, a migration may need to be customized based
 * on data that's present in the source database; such customization is
 * implemented by builders.
 */
interface MigrateBuilderInterface {

  /**
   * Builds migration entities based on a template.
   *
   * @param array $template
   *   The parsed template.
   *
   * @return \Drupal\migrate\Entity\MigrationInterface[]
   *   The unsaved migrations generated from the template.
   */
  public function buildMigrations(array $template);

}
