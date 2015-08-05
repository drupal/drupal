<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrationBuilder.
 */

namespace Drupal\migrate;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Plugin\MigratePluginManager;

/**
 * Builds migration entities from migration templates.
 */
class MigrationBuilder {

  /**
   * The builder plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $builderManager;

  /**
   * Constructs a MigrationBuilder.
   *
   * @param \Drupal\migrate\Plugin\MigratePluginManager $builder_manager
   *   The builder plugin manager.
   */
  public function __construct(MigratePluginManager $builder_manager) {
    $this->builderManager = $builder_manager;
  }

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
  public function createMigrations(array $templates) {
    /** @var \Drupal\migrate\Entity\MigrationInterface[] $migrations */
    $migrations = [];

    foreach ($templates as $template_id => $template) {
      if (isset($template['builder'])) {
        $variants = $this->builderManager
          ->createInstance($template['builder']['plugin'], $template['builder'])
          ->buildMigrations($template);
      }
      else {
        $variants = array(Migration::create($template));
      }

      /** @var \Drupal\migrate\Entity\MigrationInterface[] $variants */
      foreach ($variants as $variant) {
        $variant->set('template', $template_id);
      }
      $migrations = array_merge($migrations, $variants);
    }

    return $migrations;
  }

}
