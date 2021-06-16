<?php

namespace Drupal\migrate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a migration destination plugin annotation object.
 *
 * Plugin Namespace: Plugin\migrate\destination
 *
 * For a working example, see
 * \Drupal\migrate\Plugin\migrate\destination\UrlAlias
 *
 * @see \Drupal\migrate\Plugin\MigrateDestinationInterface
 * @see \Drupal\migrate\Plugin\migrate\destination\DestinationBase
 * @see \Drupal\migrate\Plugin\MigrateDestinationPluginManager
 * @see \Drupal\migrate\Annotation\MigrateSource
 * @see \Drupal\migrate\Annotation\MigrateProcessPlugin
 * @see plugin_api
 *
 * @ingroup migration
 *
 * @Annotation
 */
class MigrateDestination extends Plugin {

  /**
   * A unique identifier for the process plugin.
   *
   * @var string
   */
  public $id;

  /**
   * Whether requirements are met.
   *
   * If TRUE and a 'provider' key is present in the annotation then the
   * default destination plugin manager will set this to FALSE if the
   * provider (module/theme) doesn't exist.
   *
   * @var bool
   */
  public $requirements_met = TRUE;

  /**
   * Identifies the system handling the data the destination plugin will write.
   *
   * The destination plugin itself determines how the value is used. For
   * example, Migrate Drupal's destination plugins expect destination_module to
   * be the name of a module that must be installed on the destination.
   *
   * @var string
   */
  public $destination_module;

}
