<?php

/**
 * @file
 * Contains \Drupal\migrate\Annotation\MigrateSource.
 */

namespace Drupal\migrate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a migration source plugin annotation object.
 *
 * Plugin Namespace: Plugin\migrate\source
 *
 * For a working example, check
 * \Drupal\migrate\Plugin\migrate\source\EmptySource
 * \Drupal\migrate_drupal\Plugin\migrate\source\UrlAlias
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Plugin\MigrateSourceInterface
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see \Drupal\migrate\Annotation\MigrateProcessPlugin
 * @see \Drupal\migrate\Annotation\MigrateDestination
 * @see plugin_api
 *
 * @ingroup migration
 *
 * @Annotation
 */
class MigrateSource extends Plugin {

  /**
   * A unique identifier for the process plugin.
   *
   * @var string
   */
  public $id;

  /**
   * Whether requirements are met.
   *
   * @var bool
   */
  public $requirements_met = TRUE;

  /**
   * Identifies the system providing the data the source plugin will read.
   *
   * This can be any type, and the source plugin itself determines how the value
   * is used. For example, Migrate Drupal's source plugins expect
   * source_provider to be the name of a module that must be installed and
   * enabled in the source database.
   *
   * @see \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase::checkRequirements
   *
   * @var mixed
   */
  public $source_provider;

  /**
   * Specifies the minimum version of the source provider.
   *
   * This can be any type, and the source plugin itself determines how it is
   * used. For example, Migrate Drupal's source plugins expect this to be an
   * integer representing the minimum installed database schema version of the
   * module specified by source_provider.
   *
   * @var mixed
   */
  public $minimum_version;

}
