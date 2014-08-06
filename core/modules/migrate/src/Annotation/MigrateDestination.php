<?php

/**
 * @file
 * Contains \Drupal\migrate\Annotation\MigrateDestination.
 */

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
 * @see \Drupal\migrate\Plugin\destination\DestinationBase
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
   * A class to make the plugin derivative aware.
   *
   * @var string
   *
   * @see \Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator
   */
  public $derivative;

}
