<?php

/**
 * @file
 * Contains \Drupal\migrate\Annotation\MigrateDestination.
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

}
