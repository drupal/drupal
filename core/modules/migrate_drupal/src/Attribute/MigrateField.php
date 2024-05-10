<?php

declare(strict_types=1);

namespace Drupal\migrate_drupal\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a field plugin attribute object.
 *
 * Field plugins are responsible for handling the migration of custom fields
 * (provided by Field API in Drupal 7) to Drupal 8+. They are allowed to alter
 * fieldable entity migrations when these migrations are being generated, and
 * can compute destination field types for individual fields during the actual
 * migration process.
 *
 * Plugin Namespace: Plugin\migrate\field
 *
 * For a working example, see
 * \Drupal\datetime\Plugin\migrate\field\DateField
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate_drupal\Plugin\MigrateFieldInterface;
 * @see \Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase
 * @see plugin_api
 *
 * @ingroup migration
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class MigrateField extends Plugin {

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected $definition;

  /**
   * Constructs a migrate field attribute object.
   *
   * @param string $id
   *   A unique identifier for the field plugin.
   * @param int[] $core
   *   (optional) The Drupal core version(s) this plugin applies to.
   * @param int $weight
   *   (optional) The weight of this plugin relative to other plugins servicing
   *   the same field type and core version. The lowest weighted applicable
   *   plugin will be used for each field.
   * @param string[] $type_map
   *   (optional) Map of D6 and D7 field types to D8+ field type plugin IDs.
   * @param string|null $source_module
   *   (optional) Identifies the system providing the data the field plugin will
   *   read. The source_module is expected to be the name of a Drupal module
   *   that must be installed in the source database.
   * @param string|null $destination_module
   *   (optional) Identifies the system handling the data the destination plugin
   *   will write. The destination_module is expected to be the name of a Drupal
   *   module on the destination site that must be installed.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly array $core = [6],
    public readonly int $weight = 0,
    public readonly array $type_map = [],
    public readonly ?string $source_module = NULL,
    public readonly ?string $destination_module = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
