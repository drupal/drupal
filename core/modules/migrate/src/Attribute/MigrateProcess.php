<?php

declare(strict_types=1);

namespace Drupal\migrate\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a MigrateProcess attribute.
 *
 * Plugin Namespace: Plugin\migrate\process
 *
 * For a working example, see
 * \Drupal\migrate\Plugin\migrate\process\DefaultValue
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 * @see \Drupal\migrate\ProcessPluginBase
 * @see \Drupal\migrate\Attribute\MigrateDestination
 * @see plugin_api
 *
 * @ingroup migration
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MigrateProcess extends Plugin {

  /**
   * Constructs a migrate process plugin attribute object.
   *
   * @param string $id
   *   A unique identifier for the process plugin.
   * @param bool $handle_multiples
   *   (optional) Whether the plugin handles multiples itself. Typically these
   *   plugins will expect an array as input and iterate over it themselves,
   *   changing the whole array. For example the 'sub_process' and the 'flatten'
   *   plugins. If the plugin only needs to change a single value, then it can
   *   skip setting this attribute and let
   *   \Drupal\migrate\MigrateExecutable::processRow() handle the iteration.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly bool $handle_multiples = FALSE,
    public readonly ?string $deriver = NULL,
  ) {}

}
