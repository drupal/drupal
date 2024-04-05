<?php

declare(strict_types=1);

namespace Drupal\migrate\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a MigrateDestination attribute.
 *
 * Plugin Namespace: Plugin\migrate\destination
 *
 * For a working example, see
 * \Drupal\migrate\Plugin\migrate\destination\UrlAlias
 *
 * @see \Drupal\migrate\Plugin\MigrateDestinationPluginManager
 * @see \Drupal\migrate\Plugin\MigrateDestinationInterface
 * @see \Drupal\migrate\Plugin\migrate\destination\DestinationBase
 * @see \Drupal\migrate\Attribute\MigrateProcess
 * @see plugin_api
 *
 * @ingroup migration
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MigrateDestination extends Plugin {

  /**
   * Constructs a migrate destination plugin attribute object.
   *
   * @param string $id
   *   A unique identifier for the destination plugin.
   * @param bool $requirements_met
   *   (optional) Whether requirements are met.
   * @param string|null $destination_module
   *   (optional) Identifies the system handling the data the destination plugin
   *   will write. The destination plugin itself determines how the value is
   *   used. For example, Migrate's destination plugins expect
   *   destination_module to be the name of a module that must be installed on
   *   the destination.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public bool $requirements_met = TRUE,
    public readonly ?string $destination_module = NULL,
    public readonly ?string $deriver = NULL,
  ) {
  }

}
