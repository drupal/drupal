<?php

namespace Drupal\migrate_drupal\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a field plugin annotation object.
 *
 * Field plugins are responsible for handling the migration of custom fields
 * (provided by CCK in Drupal 6 and Field API in Drupal 7) to Drupal 8. They are
 * allowed to alter fieldable entity migrations when these migrations are being
 * generated, and can compute destination field types for individual fields
 * during the actual migration process.
 *
 * Plugin Namespace: Plugin\migrate\field
 *
 * @Annotation
 */
class MigrateField extends Plugin {

  /**
   * @inheritdoc
   */
  public function __construct($values) {
    parent::__construct($values);
    // Provide default value for core property, in case it's missing.
    if (empty($this->definition['core'])) {
      $this->definition['core'] = [6];
    }
  }

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Map of D6 and D7 field types to D8 field type plugin IDs.
   *
   * @var string[]
   */
  public $type_map = [];

  /**
   * The Drupal core version(s) this plugin applies to.
   *
   * @var int[]
   */
  public $core;

  /**
   * Identifies the system providing the data the field plugin will read.
   *
   * The source_module is expected to be the name of a Drupal module that must
   * be installed in the source database.
   *
   * @var string
   */
  public $source_module;

  /**
   * Identifies the system handling the data the destination plugin will write.
   *
   * The destination_module is expected to be the name of a Drupal module on the
   * destination site that must be installed.
   *
   * @var string
   */
  public $destination_module;

}
