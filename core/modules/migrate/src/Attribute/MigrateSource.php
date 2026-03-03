<?php

declare(strict_types=1);

namespace Drupal\migrate\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a MigrateSource attribute.
 *
 * Plugin Namespace: Plugin\migrate\source
 *
 * For a working example, see
 * \Drupal\migrate\Plugin\migrate\source\EmptySource
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Plugin\MigrateSourceInterface
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see \Drupal\migrate\Attribute\MigrateDestination
 * @see \Drupal\migrate\Attribute\MigrateProcess
 * @see plugin_api
 *
 * @ingroup migration
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MigrateSource extends Plugin {

  /**
   * Constructs a migrate source plugin attribute object.
   *
   * @param string $id
   *   A unique identifier for the source plugin.
   * @param bool $requirements_met
   *   (optional) Whether requirements are met. Defaults to true. The source
   *   plugin itself determines how the value is used.
   * @param mixed $minimum_version
   *   (optional) Specifies the minimum version of the source provider. This can
   *   be any type, and the source plugin itself determines how it is used.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public bool $requirements_met = TRUE,
    public readonly mixed $minimum_version = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
